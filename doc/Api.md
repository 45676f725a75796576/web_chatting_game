# Api
*API between the frontend and backend*

## info
The communication is through sending JSON packets divided by a new line character. All packets have a "type" string that indicates the packet type.

## Global behaviour
The user disconnects when the WebSocket closes. On disconnect the server sends a "server_disconnect" packet to all the other clients.


## Before multiplayer session

### Creating a new user
1. client sends a json packet of type "sign_in"
2. server respons with a json packet of type "server_sign_in"
3. WebSocket session is now authenticated

### Login
1. client sends a json packet of type "login"
2. server respons with a json packet of type "server_login"
3. WebSocket session is now authenticated

### joining the server
1. client sends a "enter_game" packet
2. server responds with a "server_room" packet
3. the [multiplayer session](#multiplayer-session) starts

## multiplayer session
in the multiplayer session the client can send movement commands at any time and the server can send player updates at any time

### moving a player in the multiplayer session
1. client sends a "player_pos" packet
2. server **does not respond**
3. server sends a "server_player_pos" packet to all other clients joined in the session

### user moves into another room
1. client sends a "enter_room" packet
2. server responds with packet "server_room"
3. server sends a "server_disconnect" packet to all other clients joined in the session

### user moves into another floor
1. client sends a "enter_floor" packet
2. server responds with packet "server_floor"
3. server sends a "server_disconnect" packet to all other clients joined in the session

### user sends a chat message
1. client sends a "chat" packet
2. server does not respond
3. server sends a "server_chat" packet to all other clients joined in the session

## packets

### sign_in
```
{
    "type": "sign_in"
    "username": <players username>
}
```

### server_sign_in
- success
```
{
    "type":"server_sign_in",
    "state": "success",
    "identifier_str":<a unique string identifying this user>,
    "player_id": <id of the player>,
    "img": <url to the player skin>
}
```
- error
```
{
    "type":"server_sign_in",
    "state": "error",
    "message": <error message>
}
```

### login
```
{
    "type": "login",
    "username": <username>
    "identifier_str": <identifier string>
}
```

### server_login
- success
```
{
    "type":"server_login",
    "state": "success",
    "player_id": <id of the player>,
    "img": <url to the player skin>
}
```
- error
```
{
    "type":"server_login",
    "state": "error",
    "message": <error message>
}
```

### enter_game
```
{
    "type": "enter_game"
}
```

### server_room
- success
```
{
    "type": "server_room",
    "state": "success",
    "img": <url to the room image>,
    "room_id": <id of the room>,
    "floor": <the floor this room is at>
}

```
- error
```
{
    "type": "server_room",
    "state": "error",
    "message": <message>
}
```
### server_floor
- success
```
{
    "type": "server_floor",
    "state": "success",
    "img": <url to the floor image>,
    "floor_id": <id of the floor>,
    "rooms": [
        <room id>,
        <room id>,
        <room id>,
        <room id>,
    ]
}

```
- error
```
{
    "type": "server_floor",
    "state": "error",
    "message": <message>
}
```

## player_pos
```
{
    "type": "player_pos",
    "pos": {
        "x": <x position>,
        "y": <y position>
    }
}
```

## server_player_pos
```
{
    "type": "server_player_pos",
    "player_id": <id of the moved player>,
    "pos": {
        "x": <x position>,
        "y": <y position>
    }
}
```

## enter_floor
```
{
    "type": "enter_floor",
    "floor_id": <id of the floor>,
}
```

## enter_room
```
{
    "type": "enter_room",
    "room_id": <id of the room>,
}
```

## server_disconnect
```
{
    "type": "server_disconnect",
    "player_id": <id of the disconected player>
}
```

<<<<<<< Updated upstream
## server_new_player
```
{
    "type": "server_new_player",
    "player_id": <player id>,
    "username": <player username>,
    "img": <player skin>,
    "pos": {
        "x": <pos x>,
        "y": <pos y>
    }
=======
### chat
```
{
    "player_id": <players id>,
    "message": <text message>
}
```

### server_chat
**success:** 
```
{
    "state": "success",
    "player_id": <players id>,
    "message": <text message>,
    "timeout": <number of seconds>
}
```

**error:** 
```
{
    "state": "error",
    "message": <error message>,
>>>>>>> Stashed changes
}
```