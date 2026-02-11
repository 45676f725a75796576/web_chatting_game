# Game Design Document

*Game made by Erik Loskorikh, Martin Moravek, Maxim Mazuret, Jegor Zujev*

## Main game mechanics

Player is moving in top-down 2D dimension.  
Player can chat with other players.  
Player has own apartment in infinite panel apartment building.  
Player can leave his flat to the corridor and go to other flats, if is not locked.  
Player can lock his apartment.  
Player can travel between floors on elevator by entering player id.  
Player can select how apartment and player looks from presets.  
Player can see messages that were sent in last ten seconds above senders head.  
Player can send messages.
Messages are filtered on banned words.

## Gameloop

    a) Player spawns in his apartment, goes out, goes to the elevator and enters other players id. Elevator sends him on the floor with apartments where can be wanted player. Player gets into apartment and starts chatting. 
    b) Player spawns, unlocks his apartment and starts chatting with guests.

## Visual

2D pixel art. 
