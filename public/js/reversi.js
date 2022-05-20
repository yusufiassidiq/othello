// Set the board from the board string
function setBoardContent() {        
    // Split string into valid X coord lengths
    boardContent = str_split(boardContentString, gridSize);
    
    // Loop over each Y coord...
    for (var i = 0; i < gridSize; i++) {
        // ... and insert each X coord
        boardContent[i] = str_split(boardContent[i], 1);
    }
}

// Try and place a coin on the board
function doTurn() {
    // is there already a coin in this coord?
    if (boardContent[y][x] != '-') {
        return false;
    }

    // Place the users coin on the board
    boardContent[y][x] = turnInPlay;
    
    // Did we take any of our opponants coins?
    checkCoinAround(0, -1);  // Top
    checkCoinAround(1, -1);  // Top right
    checkCoinAround(1, 0);   // Right
    checkCoinAround(1, 1);   // Bottom right
    checkCoinAround(0, 1);   // Bottom
    checkCoinAround(-1, 1);  // Bottom left
    checkCoinAround(-1, 0);  // Left
    checkCoinAround(-1, -1); // Top left
}

// Begin the reversing of the coin
function checkCoinAround(xDiff, yDiff) {
    // Set variables
    xTemp = x;
    yTemp = y;
    continueOn = true;
    
    // Begin the loop
    do {
        // Work out the new coords to test
        xTemp += xDiff;
        yTemp += yDiff;

        // What is in the next position?
        next = typeof boardContent[yTemp] != "undefined" && typeof boardContent[yTemp][xTemp] != "undefined"
            ? boardContent[yTemp][xTemp]
            : 'e'; // Edge

        // Have we hit an edge or an empty position?
        if (next == 'e' || next == '-') {
            continueOn = false;
        }

        // Have we reached our own coin colour?
        else if (next == turnInPlay) {
            // We are currently at our own coin, move back one so we are at our
            // .. last free (potentially) coin.
            if (xDiff > 0) { xTemp--; } else if (xDiff < 0) { xTemp++; }
            if (yDiff > 0) { yTemp--; } else if (yDiff < 0) { yTemp++; }
            
            // Are we where we started?
            while (xTemp != x || yTemp != y) {
                // Change this coin to the player who just moved
                boardContent[yTemp][xTemp] = turnInPlay;
                
                // Change the image
                $("div[rel='"+xTemp+":"+yTemp+"']").removeClass('coin-'+theOtherColorCoin).addClass('coin-'+colorCoin).css('background-image',backgroundColor);
                
                // Set which coin we just updated
                coinsChanged[coinsChanged.length] = [xTemp, yTemp];

                // Move back one coord to begin another replacement
                if (xDiff > 0) { xTemp--; } else if (xDiff < 0) { xTemp++; }
                if (yDiff > 0) { yTemp--; } else if (yDiff < 0) { yTemp++; }
            }
            
            // We have converted all of the possible coins, exit the traverse
            continueOn = false;
        }
    } while (continueOn);
}

// When we hover away from an empty coin we need to reset the board
function resetCoins() {
    // Change the empty coin back to empty
    boardContent[y][x] = "-";
    
    // Set variables
    var coinsChangedLength = coinsChanged.length;
    
    // Loop over changed coins
    for (var i = 0; i < coinsChangedLength; i++) {
        // Reset coin image
        $("div[rel='"+coinsChanged[i][0]+":"+coinsChanged[i][1]+"']").removeClass('coin-'+colorCoin).addClass('coin-'+theOtherColorCoin).css('background-image',backgroundColor2);
        
        // Reset the board
        boardContent[coinsChanged[i][1]][coinsChanged[i][0]] = turnNext;
    }
    
    // And reset the discs changed
    coinsChanged = new Array;
}

// The DOM is ready
$(document).ready(function() {
    // Wait for the player to mouseover an empty disc
    $(".coin-empty").hover(function() {
        // Set the coin colour by append new child div
        $(this).append('<div style="position:relative;background-image:'+backgroundColor+';width:50px;height:50px;border-radius:50%;" class="coin-'+colorCoin+'"></div>')
        // Set the X and Y coords
        coords = $(this).attr("rel");
        coords = coords.split(':');
        x      = parseInt(coords[0]);
        y      = parseInt(coords[1]);
        
        // Do turn
        doTurn();
    }).mouseleave(function() {
        // Reset the coin that the user hovered over with remove child div
        $(this).children('.coin-'+colorCoin).remove();
        // Reset the coins we changed
        resetCoins();
    });
});