<a id="readme-top"></a>
## Web chat game

---


<details>
  <summary>Table of Contents</summary>
  <ol>
    <li><a href="#web-chat-game">Web Chat Game</a></li>
    <li><a href="#what-the-application-will-do">What the Application Will Do</a></li>
    <li><a href="#target-audience">Target Audience</a></li>
    <li><a href="#core-player-features">Core Player Features</a></li>
    <li><a href="#built-with">Built With</a></li>
  </ol>
</details>

## What the Application Will Do

The application is a web-based 2D online game with a top-down view, in which players can move through an infinite panel apartment building, communicate with each other, and manage their own apartment. A player can enter individual apartments (if they are not locked), lock their own apartment, travel between floors using an elevator, and customize the appearance of both the player and the apartment using preset options.

The application also includes a chat system where messages appear above the sender’s character and are filtered for inappropriate words. An administrator has the ability to manage users and the application’s content.

<p align="right">(<a href="#readme-top">back to top</a>)</p>

---

## Target Audience

The application is primarily intended for younger and older players who are interested in simple online social games, communication with other players, and a virtual environment similar to a community apartment building. It can also be used in school projects or as a demonstration of a web-based multiplayer application.

<p align="right">(<a href="#readme-top">back to top</a>)</p>

---

## System Analysis

### Stakeholders

| Stakeholder | Description |
|-------------|-------------|
| Player | Uses the application to move in the game world, communicate with others, and manage their apartment. |
| Administrator | Manages users, moderates content, and ensures safe operation of the system. |

### Use Cases

| Actor | Use Case | Description |
|-------|----------|-------------|
| Player | Log in | Player signs into the game. |
| Player | Move in building | Player moves inside the 2D environment. |
| Player | Enter apartment | Player enters unlocked apartments. |
| Player | Lock apartment | Player locks their own apartment. |
| Player | Use elevator | Player travels between floors. |
| Player | Send message | Player sends a chat message. |
| Player | View messages | Player sees messages from others. |
| Player | Customize appearance | Player changes character and apartment look. |
| Admin | Manage users | Administrator edits or removes users. |
| Admin | Moderate chat | Administrator deletes messages or blocks players. |

### Functional Requirements

| ID | Requirement Description |
|----|-------------------------|
| FR-01 | The system shall allow a player to log in. |
| FR-02 | The system shall allow player movement in a 2D building environment. |
| FR-03 | The system shall allow entry into unlocked apartments. |
| FR-04 | The system shall allow a player to lock their own apartment. |
| FR-05 | The system shall allow travel between floors via elevator. |
| FR-06 | The system shall support real-time sending and receiving of chat messages. |
| FR-07 | The system shall display recent messages above the sender’s character. |
| FR-08 | The system shall allow customization of player and apartment appearance. |
| FR-09 | The system shall allow an administrator to manage users. |
| FR-10 | The system shall allow an administrator to moderate chat and block players. |

<p align="right">(<a href="#readme-top">back to top</a>)</p>

### Built With

This section lists the main technologies used to build the project.

* [![PHP][PHP]][PHP-url]
* [![MySQL][MySQL]][MySQL-url]
* [![HTML5][HTML5]][HTML5-url]
* [![CSS3][CSS3]][CSS3-url]
* [![JavaScript][JavaScript]][JavaScript-url]


<!-- MARKDOWN LINKS & IMAGES -->
[PHP]: https://img.shields.io/badge/PHP-8.1-777BB4?logo=php&logoColor=white
[PHP-url]: https://www.php.net/

[MySQL]: https://img.shields.io/badge/MySQL-Database-4479A1?logo=mysql&logoColor=white
[MySQL-url]: https://www.mysql.com/

[HTML5]: https://img.shields.io/badge/HTML5-Markup-E34F26?logo=html5&logoColor=white
[HTML5-url]: https://developer.mozilla.org/en-US/docs/Web/HTML

[CSS3]: https://img.shields.io/badge/CSS3-Styles-1572B6?logo=css3&logoColor=white
[CSS3-url]: https://developer.mozilla.org/en-US/docs/Web/CSS

[JavaScript]: https://img.shields.io/badge/JavaScript-ES6-F7DF1E?logo=javascript&logoColor=black
[JavaScript-url]: https://developer.mozilla.org/en-US/docs/Web/JavaScript

<p align="right">(<a href="#readme-top">back to top</a>)</p>

