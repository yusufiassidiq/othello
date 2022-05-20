<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/style.css') }}">

    </head>
    <body>
        <div class="flex-center position-ref full-height">
            <div class="content">
                <form action="{{route('play-the-game')}}" method="get">
                <h4>Welcome to the Othello Game</h4>

                <p>How size of the board ?</p>
                <input name="gridSize" type="number" placeholder="6, 8 or 10"/>
                <select name="mode">
                    <option disabled selected>Select Mode Game</option>
                    <option value="pvp">Player vs Player</option>
                    <option value="pvai">Player vs AI</option>
                </select>
                <button type="submit">Lets play the game!</button>
                </form>
            </div>
        </div>
    </body>
</html>
