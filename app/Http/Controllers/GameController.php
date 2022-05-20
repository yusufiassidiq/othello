<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GameController extends Controller
{
    //Define all the required variables
    private $_mode = 'pvp';
    //Size of grid box
    private $_gridSize = 8;

    //Board in array form
    private $_boardContent;

    //Board in array form after turn
    private $_boardContentAfterTurn;

    //Board in array after turn for suggestion
    private $_boardAfterTurnSuggest;

    //Coordinate for X
    private $_x = false;

    //Coordinate for Y
    private $_y = false;

    //Player who has just played this move
    private $_turnInPlay;

    //Player who turn next
    private $_turnNext;

    //Total the coin when flipped
    private $_coinsFlipped = 0;

    //The data coordinate suggestion
    private $_coordSuggestedMove = [];

    //The data coordinate the possibility flipped coin while in suggestion
    private $_coordFlippedCoinSuggestedMove = [];

    //Total suggested move
    private $_totalSuggestedMove;

    //Variable for trace while checking suggestion move
    private $_trace = [];

    public function welcomePage(Request $request)
    {
        return view('welcome');
    }

    public function index(Request $request)
    {
        if(isset($request->gridSize)){
            if($request->gridSize <= 4){
                $request->gridSize = 4;
            }
            if($request->gridSize%2 == 1){
                $request->gridSize+=1;
            }
            $this->_gridSize = $request->gridSize;
            $request->session()->put('gridSize', $request->gridSize);
        }else{
            $this->_gridSize = $request->session()->get('gridSize', 8);
        }

        if(isset($request->mode)){
            $this->_mode = $request->mode;
            $request->session()->put('mode', $request->mode);
        }else{
            $this->_mode = $request->session()->get('mode', 'pvp');
        }

        if($this->_mode == 'pvp'){
            $pvp = $this->pvp();
            return view('game',[
                'boardContent'=>$pvp['boardContent'],
                'gridSize'=>$pvp['gridSize'],
                'turnInPlay'=>$pvp['turnInPlay'],
                'boardContentAfterTurn'=>$pvp['boardContentAfterTurn'],
                'countCoinFlippid'=>$pvp['countCoinFlippid'],
                'isPass'=>$pvp['isPass'],
                'calculateScore'=>$pvp['calculateScore'],
                'gameStatus'=>$pvp['gameStatus'],
                'getFullName'=>$pvp['getFullName'],
                'totalSuggestedMove'=>$pvp['totalSuggestedMove'],
            ]);
        }else{
            $pvai = $this->pvai();
            return view('game',[
                'boardContent'=>$pvai['boardContent'],
                'gridSize'=>$pvai['gridSize'],
                'turnInPlay'=>$pvai['turnInPlay'],
                'boardContentAfterTurn'=>$pvai['boardContentAfterTurn'],
                'countCoinFlippid'=>$pvai['countCoinFlippid'],
                'isPass'=>$pvai['isPass'],
                'calculateScore'=>$pvai['calculateScore'],
                'gameStatus'=>$pvai['gameStatus'],
                'getFullName'=>$pvai['getFullName'],
                'totalSuggestedMove'=>$pvai['totalSuggestedMove'],
            ]);
        }
        
    }

    public function pvp()
    {
        //Setup the board
        $this->setBoardString();
        $this->setBoardContent();
        $this->setCoords();
        $this->setTurn();

        //check whether player pass the turn or not
        if(!(isset($_GET['isPass']))){
            //Do the turn if player didn't pass his turn
            $this->doTurn();
        }

        //Recheck and do the clean up board
        $this->doCleanup();
        
        //get the variable for the front end
        $pvp['boardContent'] = $this->_boardContent;
        $pvp['gridSize'] = $this->_gridSize;
        $pvp['turnInPlay'] = $this->_turnInPlay;
        $pvp['boardContentAfterTurn'] = $this->_boardContentAfterTurn;
        $pvp['countCoinFlippid'] = $this->_coinsFlipped;

        //check is the player pass the turn
        $pvp['isPass'] = $this->isPass();

        //Fill board after player turn suggestion to array
        $this->getBoardAfterTurnSuggest();

        //calculate the score and game status
        $pvp['calculateScore'] = $this->calculateScore();
        $pvp['gameStatus'] = $this->gameStatus();
        $pvp['getFullName'] = $this->getFullName($pvp['turnInPlay']);

        //Function for suggestion move
        $this->suggestion();

        // return $this->_trace;
        // return $this->_coordFlippedCoinSuggestedMove;
        // return $this->_coordSuggestedMove;

        //Insert suggested coord to string board
        $this->insertSuggestedMoveToBoard();
        $pvp['totalSuggestedMove'] = $this->_totalSuggestedMove;
        $pvp['boardContent'] = $this->_boardContent;

        return $pvp;
    }

    public function pvai()
    {
        //Setup the board
        $this->setBoardString();
        $this->setBoardContent();
        $this->setCoords();
        $this->setTurn();

        //check whether player pass the turn or not
        if(!(isset($_GET['isPass']))){
            //Do the turn if player didn't pass his turn
            $this->doTurn();
        }

        //Recheck and do the clean up board
        $this->doCleanup();
        
        //get the variable for the front end
        $pvai['boardContent'] = $this->_boardContent;
        $pvai['gridSize'] = $this->_gridSize;
        $pvai['turnInPlay'] = $this->_turnInPlay;
        $pvai['boardContentAfterTurn'] = $this->_boardContentAfterTurn;
        $pvai['countCoinFlippid'] = $this->_coinsFlipped;

        //check is the player pass the turn
        $pvai['isPass'] = $this->isPass();

        //Fill board after player turn suggestion to array
        $this->getBoardAfterTurnSuggest();

        //calculate the score and game status
        $pvai['calculateScore'] = $this->calculateScore();
        $pvai['gameStatus'] = $this->gameStatus();
        $pvai['getFullName'] = $this->getFullName($pvai['turnInPlay']);

        //Function for suggestion move
        $this->suggestion();

        // return $this->_trace;
        // return $this->_coordFlippedCoinSuggestedMove;
        // return $this->_coordSuggestedMove;

        //Insert suggested coord to string board
        $this->insertSuggestedMoveToBoard();
        $pvai['totalSuggestedMove'] = $this->_totalSuggestedMove;
        $pvai['boardContent'] = $this->_boardContent;

        return $pvai;
    }

    //Set the board in string form
    public function setBoardString()
    {
        // Do we have a board to use already?
        if (isset($_GET['board'])) {
            // Yes, use that
            $this->_boardContent = $_GET['board'];
        } else {
            // No, create a fresh board
            $this->_boardContent = str_repeat('-', ($this->_gridSize * $this->_gridSize));

            // Set the default pieces in the center of the board
            $startX = ($this->_gridSize / 2) - 1;
            $startX = ($startX * $this->_gridSize) + $startX;
            $this->_boardContent = substr_replace($this->_boardContent, 'wb', $startX, 2);
            $this->_boardContent = substr_replace($this->_boardContent, 'bw', ($startX + $this->_gridSize), 2);
        }
    }

    //Set the board in array 2 dimension form
    public function setBoardContent() {
        // Set the board string encase no move is made
        $this->_boardContentAfterTurn = $this->_boardContent;

        // Split string into valid X coord lengths
        $this->_boardContent = str_split($this->_boardContent, $this->_gridSize);
        
        // Loop over each Y coord...
        foreach ($this->_boardContent as $index => $line) {
            // ... and insert each X coord
            $this->_boardContent[$index] = str_split($this->_boardContent[$index], 1);
        }
    }

    //Set the turn
    public function setTurn() {
        // Set to black if there is no turn set, or it is blacks turn
        $this->_turnInPlay = ! isset($_GET['turn']) || $_GET['turn'] == 'b'
            ? 'b'
            : 'w';

        $this->_turnNext = ! isset($_GET['turn']) || $_GET['turn'] == 'b'
        ? 'w'
        : 'b';

    }

    //Check and set the coordinate
    public function setCoords() {
        // X coord
        if (isset($_GET['x'])) {
            $this->_x = $_GET['x'];
        }
        
        // Y coord
        if (isset($_GET['y'])) {
            $this->_y = $_GET['y'];
        }
    }

    //Do the turn
    public function doTurn() {
        // Do we need to make a move?
        if ($this->_x === false || $this->_y === false) {
            return false;
        }
        
        // Are the coords valid? //ada gak kotak di kordinat ini
        else if (! isset($this->_boardContent[$this->_y][$this->_x])) {
            return false;
        }
        
        // Is there already a coin in this coord?  //board stringnya - atau bukan, kalau iya false
        else if ($this->_boardContent[$this->_y][$this->_x] != '-') {
            return false;
        }

        // Place the users coin on the board
        $this->_boardContent[$this->_y][$this->_x] = $this->_turnInPlay;
        
        // Check if we take any of our opponants coins
        $this->checkCoinAround(0, -1);  // Top
        $this->checkCoinAround(1, -1);  // Top right
        $this->checkCoinAround(1, 0);   // Right
        $this->checkCoinAround(1, 1);   // Bottom right
        $this->checkCoinAround(0, 1);   // Bottom
        $this->checkCoinAround(-1, 1);  // Bottom left
        $this->checkCoinAround(-1, 0);  // Left
        $this->checkCoinAround(-1, -1); // Top left
    }

    //function for check
    public function checkCoinAround($xDiff, $yDiff) {
        // Set variables
        $x = $this->_x; //$this->_x = 4
        $y = $this->_y; //$this->_y = 5
        $continue = true;
        
        // Begin the loop
        do {
            // Work out the new coords to test
            $x += $xDiff; //4+0=4 //4+0=4
            $y += $yDiff; //5-1=4 //4-1=3
            
            // What is in the next position? and check the edge
            $next = isset($this->_boardContent[$y][$x])
                ? $this->_boardContent[$y][$x] // $this->_boardContent[4][4] / white // $this->_boardContent[4][3] / black
                : 'e'; // Edge

            // Have we hit an edge or an empty position?
            if ($next == 'e' || $next == '-') {
                $continue = false;
            }
            
            // Have we reached our own coin colour?
            else if ($next == $this->_turnInPlay) { //white!=black (false) // black==black (true) **ket: $this->_turnInPlay in first iteration = black
                // We are currently at our own coin, move back one so we are at our
                // .. last free (potentially) coin.
                if ($xDiff > 0) { $x--; } else if ($xDiff < 0) { $x++; } //x = 4
                if ($yDiff > 0) { $y--; } else if ($yDiff < 0) { $y++; } //y = 3++ = 4
                
                // Are we where we started?
                while ($x != $this->_x || $y != $this->_y) { // {4 != 4 (false) || 4 != 5 (true)} = true //{4 != 4 (false) || 5 != 5 (false)} = false
                    // Change this coin to the player who just moved
                    $this->_boardContent[$y][$x] = $this->_turnInPlay; //_boardContent[4][4]=white goes to black
                    
                    // Set the number of coins this flipped
                    $this->_coinsFlipped++;
                    
                    // Move back one coord to begin another replacement
                    if ($xDiff > 0) { $x--; } else if ($xDiff < 0) { $x++; } //x = 4
                    if ($yDiff > 0) { $y--; } else if ($yDiff < 0) { $y++; } //y = 5 //goback loop to while condition
                }
                
                // We have converted all of the possible coins, exit the traverse
                $continue = false;
            }
        } while ($continue);
    }

    //Recheck the last move
    public function doCleanup() {
        // Did we actually flip any coins (if we did then it must be valid)
        if ($this->_coinsFlipped >= 1) {
            $this->_turnInPlay = $this->_turnInPlay == 'b'
                ? 'w'
                : 'b';
        }
        
        // Were the coords set, but was an invalid move?
        else if (! $this->getIsValidMove()) {
            // Reset the coin
            $this->_boardContent[$this->_y][$this->_x] = '-';
        }
        
        // All moves have finished, save the board from array to string
        $this->_boardContentAfterTurn = $this->getBoardAfterTurn();
    }

    //Check is last move is a valid move
    public function getIsValidMove() {
        // If the user made a move and the coins flipped were none
        return isset($this->_x) && $this->_coinsFlipped <= 0
            ? false
            : true;
    }

    //Change the board from array to string
    public function getBoardAfterTurn() {
        $board = '';
        for ($y = 0; $y < $this->_gridSize; $y++) {
            $board .= implode('',$this->_boardContent[$y]);
        }
        return $board;
    }

    //calculate the score
    public function calculateScore() {
        // Get black and white
        $whiteCount = substr_count($this->_boardContentAfterTurn, 'w');
        $blackCount = substr_count($this->_boardContentAfterTurn, 'b');
        
        // Return scores
        return [
            'white' => $whiteCount,
            'black' => $blackCount,
            'empty' => ($this->_gridSize * $this->_gridSize) - ($whiteCount + $blackCount)
        ];
    }

    //get game status for decide the winner
    public function gameStatus() {
        // Get the stats
        $stats = $this->calculateScore();
        
        // Is black winning?
        if ($stats['black'] > $stats['white']) {
            return 'Black';
        }
        // Is white winning?
        else if ($stats['white'] > $stats['black']) {
            return 'White';
        }
        // It must be a tie
        return 'tie';
    }

    //Get the full color name by the words
    public function getFullName($color)
    {
        return $color=='b'?'Black':'White';
    }

    //check is the player pass the turn
    public function isPass()
    {
        if(isset($_GET['isPass'])){
            return $_GET['isPass'];
        }
    }

    //Make an array form from string board after last player do the turn
    public function getBoardAfterTurnSuggest()
    {
        $boardAfterTurnSuggest = $this->_boardContentAfterTurn;
        // dd($boardAfterTurnSuggest);
        // Split string into valid X coord lengths
        $boardAfterTurnSuggest = str_split($boardAfterTurnSuggest, $this->_gridSize);
        
        // Loop over each Y coord...
        foreach ($boardAfterTurnSuggest as $index => $line) {
            // ... and insert each X coord
            $boardAfterTurnSuggest[$index] = str_split($boardAfterTurnSuggest[$index], 1);
        }
        // assign to variable globally
        $this->_boardAfterTurnSuggest = $boardAfterTurnSuggest;
    }

    //function for suggested move to player turn
    public function suggestion()
    {
        //loop as many gridsize
        for ($i=0; $i < $this->_gridSize; $i++) { 
            for ($j=0; $j < $this->_gridSize; $j++) { 
            //call function for check every grid in every side
            $this->checkCoinAroundForSuggestion($i, $j, 0, -1, 'top'); //Top
            $this->checkCoinAroundForSuggestion($i, $j, 1, -1, 'top right'); //Top Right
            $this->checkCoinAroundForSuggestion($i, $j, 1, 0, 'right'); //Right
            $this->checkCoinAroundForSuggestion($i, $j, 1, 1, 'bottom right'); //Bottom Right
            $this->checkCoinAroundForSuggestion($i, $j, 0, 1, 'bottom'); //Bottom
            $this->checkCoinAroundForSuggestion($i, $j, -1, 1, 'bottom left'); //Bottom Left
            $this->checkCoinAroundForSuggestion($i, $j, -1, 0, 'left'); //Left
            $this->checkCoinAroundForSuggestion($i, $j, -1, -1, 'left top'); //Left Top
            }
        }
    }

    //function for check the coordinate for suggestion move
    public function checkCoinAroundForSuggestion($xCoord, $yCoord ,$xDiff, $yDiff, $position)
    {
        // Set variables
        $x = $xCoord;
        $y = $yCoord;
        $continue = true;

        $boardAfterTurnSuggest = $this->_boardAfterTurnSuggest;
        
        // Begin the loop
        do {
            //Check if board in the coordinate already have a coin then skip checking process
            if($boardAfterTurnSuggest[$yCoord][$xCoord] == 'w' || $boardAfterTurnSuggest[$yCoord][$xCoord] == 'b'){
                //Push in trace global variable
                array_push($this->_trace,'checking '.$xCoord.':'.$yCoord.' => skip cause there is a coin in the box');
                $continue = false;
            }
            // Work out the new coords to test
            $x += $xDiff;
            $y += $yDiff;
            
            // What is in the next position? and check the edge
            $next = isset($boardAfterTurnSuggest[$y][$x])
                ? $boardAfterTurnSuggest[$y][$x]
                : 'e'; // Edge

            // Have we hit an edge or an empty position?
            if ($next == 'e' || $next == '-') {
                //Gift $next a name for clearity tracing
                $flag = '';
                if($next == 'e'){$flag = 'edge';}
                else if($next == '-'){$flag = 'empty coin';}

                //Push in trace global variable
                array_push($this->_trace,'checking '.$xCoord.':'.$yCoord.' => skip cause in the '.$position.' of ' .$xCoord.':'.$yCoord.' where coordinate is '.$x.':'.$y.', there is an '.$flag.' while checking');
                $continue = false;
            }
            
            // Have we reached our own coin colour?
            else if ($next == $this->_turnInPlay) {
                // We are currently at our own coin, move back one so we are at our
                // .. last free (potentially) coin.
                if ($xDiff > 0) { $x--; } else if ($xDiff < 0) { $x++; }
                if ($yDiff > 0) { $y--; } else if ($yDiff < 0) { $y++; }
                // Are we where we started?
                while ($x != $xCoord || $y != $yCoord) {
                    // dd($xCoord.':'.$yCoord);
                    // Push to array the coordinate that can flip enemy coin
                    array_push($this->_coordFlippedCoinSuggestedMove , $x.':'.$y.'=>'.$xCoord.':'.$yCoord);
                    array_push($this->_coordSuggestedMove , $xCoord.':'.$yCoord);
                    
                    // Move back one coord to begin another replacement
                    if ($xDiff > 0) { $x--; } else if ($xDiff < 0) { $x++; }
                    if ($yDiff > 0) { $y--; } else if ($yDiff < 0) { $y++; }
                }
                
                // We have converted all of the possible coins, exit the traverse
                $continue = false;
            }
        } while ($continue);
    }

    //Insert suggested coordinates to string board for frontend
    public function insertSuggestedMoveToBoard()
    {
        //Get board after turn
        $board = $this->_boardContentAfterTurn;
        
        //Naming flag for the suggested coin, white = p, black = h
        $word = $this->_turnInPlay == 'w' ? 'p' : 'h';

        //Remove duplicated suggested coord move
        $this->_coordSuggestedMove = array_unique($this->_coordSuggestedMove);
        $this->_totalSuggestedMove = count($this->_coordSuggestedMove);

        //Merge flag in to the position of the string board
        foreach ($this->_coordSuggestedMove as $key => $value) {
            $coord = explode(':',$value);
            $coordToBoard = ($coord[1]*$this->_gridSize)+($coord[0]);
            $board = substr_replace($board, $word, $coordToBoard, 1);
        }

        // Split string into valid X coord lengths
        $this->_boardContent = str_split($board, $this->_gridSize);
        
        // Loop over each Y coord...
        foreach ($this->_boardContent as $index => $line) {
            // ... and insert each X coord
            $this->_boardContent[$index] = str_split($this->_boardContent[$index], 1);
        }
    }
}
