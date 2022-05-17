<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/style.css') }}">

        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
        <script type="text/javascript" src="{{ asset('js/str_split.js')}}"></script>
        <script type="text/javascript" src="{{ asset('js/reversi.js')}}"></script>
        <script type="text/javascript">
            // Set variables
            var gridSize           = <?php echo $gridSize; ?>,
                boardContent       = new Object,
                boardContentString = "<?php echo $boardContentAfterTurn; ?>",
                turnInPlay         = "<?php echo $turnInPlay; ?>",
                turnNext           = turnInPlay == 'b' ? 'w' : 'b',
                coords             = null,
                x                  = false,
                y                  = false,
                xTemp              = false,
                yTemp              = false,
                next               = false,
                continueOn         = true,
                coinsChanged       = new Array;
                colorCoin          = turnInPlay == 'b'?'black':'white',
                theOtherColorCoin  = turnInPlay == 'b'?'white':'black',
                
            // Setup the board
            setBoardContent();
        </script>
    </head>
    <body>
        <!-- <?php print_r($suggestion)?> -->
        {{$suggestion}}
        <div class="flex-center position-ref full-height">
            <div class="content">
                <div class="" style="">
                    <!-- define first letter -->
                    <?php $letter='a' ?>
                    <table id="board">
                        <!-- first row and first column-->
                        <td class="board-corner">&nbsp;</td>
                        <!-- loop for second column until end in first row -->
                        @for ($x=1;$x<=$gridSize;$x++)
                            <th>
                                <!-- increment letter -->
                                <?php echo strtoupper($letter++) ?>
                                <!-- {{$x-1}} -->
                            </th>
                        @endfor
                        <!-- loop for second row -->
                        @for ($y=1;$y<=$gridSize;$y++)
                            <tr>
                                <!-- define var $y in every first column in current row -->
                                <th><?php echo $y ?></th>
                                <!-- <th>{{$y-1}}</th> -->
                                <!-- loop for second column until end in current row -->
                                @for ($x=1;$x<=$gridSize;$x++)
                                    @if($boardContent[$y-1][$x-1] == 'b')
                                        <td><div style="position:relative;background-color:black;width:50px;height:50px;border-radius:50%;" class="coin-black" alt="B" rel="{{$x-1}}:{{$y-1}}"></div></td>
                                    @elseif($boardContent[$y-1][$x-1] == 'w')
                                        <td><div style="position:relative;background-color:white;width:50px;height:50px;border-radius:50%;" class="coin-white" alt="W" rel="{{$x-1}}:{{$y-1}}"></div></td>
                                    @else
                                        <td><a href="?x={{$x-1}}&y={{$y-1}}&turn={{$turnInPlay}}&board={{$boardContentAfterTurn}}" class="coin-empty-href" rel="{{$x-1}}:{{$y-1}}"><div style="position:relative;background-color:transparent;width:50px;height:50px;border-radius:50%;" class="coin-empty" alt="W" rel="{{$x-1}}:{{$y-1}}"></div></a></td>
                                    @endif
                                @endfor
                                
                            </tr>
                        @endfor
                    </table>
                </div>
            </div>
            <div>
                <h1>Game Stats</h1>
                <!-- Table statistics -->
                <table class="">
                    <thead>
                    <tr>
                        <th class="" colspan="2">Score</th>
                        <th class="">Turn</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="" style="color:white">White</td>
                        <td class="" style="color:white">{{$calculateScore['white']}}</td>
                        <td class="" style="color:white" rowspan="3">{{$getFullName}}</td>
                    </tr>
                    <tr>
                        <td class="" style="color:white">Black</td>
                        <td class="" style="color:white">{{$calculateScore['black']}}</td>
                    </tr>
                    <tr>
                        <td class="" style="color:white">Empty</td>
                        <td class="" style="color:white">{{$calculateScore['empty']}}</td>
                    </tr>
                    </tbody>
                </table>

                <!-- Stats and warning condition -->
                <!-- If there is still coin or empty coin not equal to zero -->
                @if($calculateScore['empty']!=0 && !($calculateScore['black'] == 0 || $calculateScore['white'] == 0))
                    <!-- If black or white coin's goes to zero  -->
                    @if($countCoinFlippid == 0)
                        @if(isset($_GET['x']) && !($isPass))
                        <h3>
                            <div class="error">
                                <p>You didn't flip any coins! Please select another box!</p>
                                <p>Or if you want to pass please <a id="pass-button" href="/game/?isPass=true&x=<?php echo (int)$_GET['x']; ?>&y=<?php echo (int)$_GET['y']; ?>&turn=<?php echo $_GET['turn'] == 'b' ? 'w' : 'b'; ?>&board=<?php echo htmlentities($_GET['board']); ?>">click here</href> </p>
                            </div>
                        </h3>
                        @else
                        &nbsp;
                        @endif
                    @elseif($countCoinFlippid == 1)
                    <h3>Great! {{$countCoinFlippid}} coin flipped!</h3>
                    @else
                    <h3>Great! {{$countCoinFlippid}} coins are flipped!</h3>
                    @endif
                <!-- If empty coin goes to zero -->
                @else
                    @if($gameStatus != 'tie')
                    <h3>{{$gameStatus}} is the winner! Congratulations!</h3>
                    @else
                    <h3>Its a tie! Both of you are great!</h3>
                    <!-- Give the question for rematch -->
                    @endif
                    <h3><a href="/">Do you wanna play again?</a></h3>
                @endif
            </div>
        </div>
    </body>
</html>
