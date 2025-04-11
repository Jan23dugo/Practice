<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dungeon Attack - Adventure Game</title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Press Start 2P', system-ui, -apple-system, sans-serif;
            background-color: #1a2236;
            color: #ffffff;
        }

        /* Container styles */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header styles */
        .header {
            text-align: center;
            padding: 20px 0;
        }

        .logo {
            max-width: 300px;
            margin: 0 auto;
            image-rendering: pixelated;
        }

        /* Main game section styles */
        .game-container {
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }

        .game-menu {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-width: 400px;
            margin: 0 auto;
        }

        .menu-button {
            background-color: #4a5568;
            color: #ffffff;
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
            font-family: 'Press Start 2P', cursive;
            text-align: center;
            text-decoration: none;
        }

        .menu-button:hover {
            background-color: #2d3748;
        }

        /* Social media sidebar */
        .social-sidebar {
            position: fixed;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .social-icon {
            width: 50px;
            height: 50px;
            background-color: #ffffff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s;
        }

        .social-icon:hover {
            transform: scale(1.1);
        }

        .social-icon img {
            width: 30px;
            height: 30px;
        }

        /* Banner section */
        .banner {
            width: 100%;
            height: 100px;
            background-color: rgba(0, 0, 0, 0.3);
            margin: 20px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .social-sidebar {
                position: static;
                flex-direction: row;
                justify-content: center;
                transform: none;
                margin-top: 20px;
            }

            .game-container {
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <img src="images/dungeon-logo.png" alt="Dungeon Attack" class="logo">
        </header>

        <main class="game-container">
            <div class="game-menu">
                <a href="#" class="menu-button">New Game</a>
                <a href="#" class="menu-button">Continue</a>
                <a href="#" class="menu-button">Character</a>
            </div>
        </main>

        <div class="social-sidebar">
            <a href="#" class="social-icon">
                <img src="images/playstore-icon.png" alt="Google Play Store">
            </a>
            <a href="#" class="social-icon">
                <img src="images/facebook-icon.png" alt="Facebook">
            </a>
            <a href="#" class="social-icon">
                <img src="images/instagram-icon.png" alt="Instagram">
            </a>
        </div>

        <div class="banner">
            <!-- Banner content will go here -->
        </div>
    </div>
</body>
</html> 