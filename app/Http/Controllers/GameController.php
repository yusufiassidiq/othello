<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GameController extends Controller
{
    //Define all the required variables
    
    //Size of grid box
    private $_gridSize = 8;

    //Board in array form
    private $_boardContent;

    //Board in array form after turn
    private $_boardContentAfterTurn;

    //Coordinate for X
    private $_x = false;

    //Coordinate for Y
    private $_y = false;

    //Player who has just played this move
    private $_turnInPlay;

    //Total the coin when flipped
    private $_coinsFlipped = 0;

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
        $boardContent = $this->_boardContent;
        $gridSize = $this->_gridSize;
        $turnInPlay = $this->_turnInPlay;
        $boardContentAfterTurn = $this->_boardContentAfterTurn;
        $countCoinFlippid = $this->_coinsFlipped;

        //check is the player pass the turn
        $isPass = $this->isPass();

        //calculate the score and game status
        $calculateScore = $this->calculateScore();
        $gameStatus = $this->gameStatus();
        $getFullName = $this->getFullName($turnInPlay);

        return view('game',compact('gridSize','boardContent','turnInPlay','boardContentAfterTurn','calculateScore','gameStatus','getFullName','countCoinFlippid','isPass'));
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
}
