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
2. server responds with a "server_place" packet
3. the [multiplayer session](#multiplayer-session) starts

## multiplayer session
in the multiplayer session the client can send movement commands at any time and the server can send player updates at any time

### moving a player in the multiplayer session
1. client sends a "player_pos" packet
2. server **does not respond**
3. server sends a "server_player_pos" packet to all other clients joined in the session

### user moves into another room
1. client sends a "enter_destination" packet
2. server responds with packet "server_place"
3. server sends a "server_disconnect" packet to all other clients joined in the session

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

### server_place
- success
```
{
    "type": "server_place",
    "state": "success",
    "place": {
        "img": <url to the room or floor image>,
        "id": <id of the room or floor>,
        "is_floor": <0 or 1>
    }
}

```
- error
```
{
    "type": "server_place",
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

## enter_destination
```
{
    "type": "enter_destination",
    "dest_id": <id of the room or floor>,
    "is_floor": <0 or 1>
}
```

## server_disconnect
```
{
    "type": "server_disconnect",
    "player_id": <id of the disconected player>
}
```
