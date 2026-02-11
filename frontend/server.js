const express = require('express');
const app = express();
const http = require('http').createServer(app);
const io = require('socket.io')(http);
const path = require('path');

app.use(express.static('public'));

const players = {};
const chatMessages = {}; // {playerId: {text: string, timestamp: number}}

io.on('connection', (socket) => {
    console.log('Player connected:', socket.id);

    // Создаем нового игрока
    players[socket.id] = {
        id: socket.id,
        x: Math.random() * 700 + 50,
        y: Math.random() * 500 + 50,
        color: '#' + Math.floor(Math.random()*16777215).toString(16),
        name: 'Player' + Math.floor(Math.random() * 1000)
    };

    // Отправляем текущее состояние новому игроку
    socket.emit('currentPlayers', players);
    socket.emit('yourId', socket.id);

    // Уведомляем других о новом игроке
    socket.broadcast.emit('newPlayer', players[socket.id]);

    // Обработка движения
    socket.on('playerMovement', (movementData) => {
        if (players[socket.id]) {
            players[socket.id].x = movementData.x;
            players[socket.id].y = movementData.y;
            socket.broadcast.emit('playerMoved', {
                id: socket.id,
                x: movementData.x,
                y: movementData.y
            });
        }
    });

    // Обработка чата
    socket.on('chatMessage', (message) => {
        if (players[socket.id] && message.trim()) {
            const chatData = {
                playerId: socket.id,
                text: message.substring(0, 100),
                timestamp: Date.now()
            };

            chatMessages[socket.id] = chatData;

            // Отправляем всем
            io.emit('playerChat', chatData);

            // Удаляем сообщение через 5 секунд
            setTimeout(() => {
                if (chatMessages[socket.id] && chatMessages[socket.id].timestamp === chatData.timestamp) {
                    delete chatMessages[socket.id];
                    io.emit('chatExpired', socket.id);
                }
            }, 5000);
        }
    });

    // Отключение
    socket.on('disconnect', () => {
        console.log('Player disconnected:', socket.id);
        delete players[socket.id];
        delete chatMessages[socket.id];
        io.emit('playerDisconnected', socket.id);
    });
});

const PORT = process.env.PORT || 3001;
http.listen(PORT, () => {
    console.log(`Server running on port ${PORT}`);
});
