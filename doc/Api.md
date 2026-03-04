# Api
*API between the frontend and backend*

## info
The communication is through sending JSON packets divided by a new line character. All packets have a "type" string that indicates the packet type.

## Global behaviour
The user disconnects when the WebSocket closes. On disconnect the server sends a "server_disconnect" packet to all the other clients.

All web socket packets may have packet type "server_error".

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

### utm analytics
1. client gets utm parameters and sends "utm" packet
2. client should store a cookie that utm was sent
3. server responds with "server_utm"

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


### user changes his skin
1. client sends a "skin" packet
2. server returns success or error
3. all connected players will get packet "server_skin_update"

### user changes his room skin
1. client sends a "room_skin" packet
2. server returns success or error
3. all connected players will get packet "server_room_skin_update"


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

### login
```
{
    "type": "login",
    "username": <username>
    "identifier_str": <identifier string>
}
```

### server_login
```
{
    "type":"server_login",
    "state": "success",
    "player_id": <id of the player>,
    "img": <url to the player skin>
}
```

### enter_game
```
{
    "type": "enter_game"
}
```

### server_room
```
{
    "type": "server_room",
    "state": "success",
    "img": <url to the room image>,
    "room_id": <id of the room>,
    "floor": <the floor this room is at>
}
```

### server_floor
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

### player_pos
```
{
    "type": "player_pos",
    "pos": {
        "x": <x position>,
        "y": <y position>
    }
}
```

### server_player_pos
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

### enter_floor
```
{
    "type": "enter_floor",
    "floor_id": <id of the floor>,
}
```

### enter_room
```
{
    "type": "enter_room",
    "room_id": <id of the room>,
}
```

### server_disconnect
```
{
    "type": "server_disconnect",
    "player_id": <id of the disconected player>
}
```

### chat
```
{
    "type":"chat",
    "player_id": <players id>,
    "message": <text message>
}
```

### server_chat
```
{
    "type":"server_chat",
    "state": "success",
    "player_id": <players id>,
    "message": <text message>,
    "timeout": <number of seconds>
}
```

### server_room_skin
```
{
    "type":"server_room_skin",
    "state": "success",
}
```

### server_skin
```
{
    "type":"server_skin",
    "state": "success",
}
```

### skin
```
{
    "type":"skin",
    "url": <skin url>
}
```

### room_skin
```
{
    "type":"room_skin",
    "url": <skin url>
}
```

### server_skin_update
```
{
    "type":"server_skin_update",
    "player_id":<player id>,
    "url": <skin url>
}
```

### server_room_skin_update
```
{
    "type":"server_room_skin_update",
    "url": <skin url>
}
```

### server_error
```
{
    "type": "server_error",
    "state": "error",
    "message": <error message>
```

### room_lock
```
{
    "type":"room_lock",
    "lock": <true or false>
```

### utm
```
{
    "type": "utm",
    "utm_source": <utm source>,
    "utm_campaign": <utm campaign>,
    "utm_medium": <utm medium>,
}
```

### server_utm
```
{
    "type": "server_utm",
    "state": "success"
}
```