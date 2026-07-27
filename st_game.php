<?php
// Output Buffering ආරම්භ කිරීම - Headers Sent Error එක සම්පූර්ණයෙන් වළක්වයි
ob_start(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Grandmaster Chess - Elite Edition</title> 
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script> 
    
    <!-- Font Awesome (Sidebar/Burger Menu Icons සඳහා) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Premium Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            /* සුඛෝපභෝගී රාජකීය වර්ණ සංකලනයක් (Royal Dark & Warm Gold) */
            --bg-gradient: radial-gradient(circle at 50% 50%, #1a1525 0%, #0c0914 100%); 
            --board-light: #f0d9b5; 
            --board-dark: #b58863; 
            --gold: #dfb257; 
            --gold-glow: rgba(223, 178, 87, 0.4);
            --neon-green: #10b981; 
            --neon-red: #ef4444; 
            --glass-bg: rgba(255, 255, 255, 0.02); 
            --glass-border: rgba(255, 255, 255, 0.06); 
            --sidebar-width: 250px;
        }

        * {
            box-sizing: border-box; 
            margin: 0;
            padding: 0;
        }

        body {
            background: var(--bg-gradient); 
            color: #f3f4f6; 
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif; 
            display: flex;
            min-height: 100vh;
            overflow-x: hidden; 
        }

        .page-wrapper {
            display: flex;
            width: 100vw;
            min-height: 100vh; 
        }

        /* ---------------- MOBILE TOP NAVBAR ---------------- */
        .mobile-navbar {
            display: none;
            background: #0c0914;
            border-bottom: 1px solid var(--glass-border);
            padding: 15px 20px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(10px);
        }

        .menu-toggle-btn {
            background: transparent;
            border: none;
            color: var(--gold);
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* ---------------- MAIN CONTENT AREA ---------------- */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;      
            justify-content: center;  
            padding: 40px 20px; 
            margin-left: var(--sidebar-width); 
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        h2 {
            font-family: 'Cinzel', serif;
            font-size: calc(1.6rem + 1vw); 
            margin: 0 0 5px 0; 
            font-weight: 900; 
            background: linear-gradient(135deg, #fff 30%, var(--gold) 100%); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            text-shadow: 0 4px 20px rgba(223, 178, 87, 0.15); 
            letter-spacing: 3px; 
            text-align: center; 
        }

        .info-panel {
            display: flex;
            gap: 40px; 
            margin-top: 15px;
            margin-bottom: 20px; 
            background: rgba(255, 255, 255, 0.03); 
            backdrop-filter: blur(20px); 
            -webkit-backdrop-filter: blur(20px); 
            padding: 12px 40px; 
            border-radius: 16px; 
            border: 1px solid var(--glass-border); 
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5); 
        }

        .stat-box { text-align: center; position: relative; } 
        .stat-box:first-child::after {
            content: '';
            position: absolute;
            right: -20px;
            top: 20%;
            height: 60%;
            width: 1px;
            background: rgba(255, 255, 255, 0.1);
        }
        .stat-label { font-size: 10px; font-weight: 600; text-transform: uppercase; color: #a0aec0; letter-spacing: 1.5px; margin-bottom: 4px; } 
        .stat-value { font-size: 24px; font-weight: 700; color: #fff; font-variant-numeric: tabular-nums; } 
        #score { color: var(--gold); }

        #status {
            font-size: 0.9rem; 
            margin-bottom: 25px; 
            font-weight: 700; 
            color: var(--neon-green); 
            letter-spacing: 1.5px; 
            padding: 8px 24px; 
            border-radius: 50px; 
            background: rgba(16, 185, 129, 0.08); 
            border: 1px solid rgba(16, 185, 129, 0.2); 
            text-align: center;
            text-transform: uppercase;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.05);
        }

        /* ---------------- CHESS BOARD CONTAINER ---------------- */
        .game-area {
            position: relative; 
            background: linear-gradient(135deg, #2d1f14 0%, #150e09 100%); /* පොහොසත් ලී පෙනුමක් ඇති බෝඩරය */
            padding: 16px; 
            border-radius: 20px; 
            box-shadow: 0 30px 70px rgba(0,0,0,0.85), inset 0 0 25px rgba(0,0,0,0.7); 
            border: 1px solid rgba(223, 178, 87, 0.15); 
            width: 100%;
            max-width: 530px; 
            aspect-ratio: 1 / 1;
        }

        .main-container {
            display: grid; 
            grid-template-columns: repeat(8, 1fr); 
            grid-template-rows: repeat(8, 1fr); 
            border-radius: 8px; 
            overflow: hidden; 
            width: 100%;
            height: 100%;
            box-shadow: 0 0 30px rgba(0,0,0,0.5);
        }

        .square {
            width: 100%; 
            height: 100%; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            cursor: pointer; 
            position: relative; 
            user-select: none;
            transition: background-color 0.2s ease;
        }

        .light-sq { background-color: var(--board-light); } 
        .dark-sq { background-color: var(--board-dark); } 

        .square img {
            width: 84%; 
            height: 84%; 
            z-index: 2; 
            pointer-events: none; 
            transition: transform 0.25s cubic-bezier(0.25, 1, 0.5, 1); 
            filter: drop-shadow(0 6px 5px rgba(0,0,0,0.45)); 
        }

        .square:hover img { 
            transform: scale(1.12) translateY(-4px); 
            filter: drop-shadow(0 12px 10px rgba(0,0,0,0.55));
        }
        
        /* Select කරපු කොටුවට සුඛෝපභෝගී රන්වන් පැහැති ආලෝකයක් */
        .selected { 
            background-color: rgba(223, 178, 87, 0.45) !important;
            box-shadow: inset 0 0 15px rgba(223, 178, 87, 0.6);
            z-index: 10; 
        }

        /* Move කරන්න පුළුවන් කොටු පෙන්වන Elegant Minimalist Dot එකක් */
        .valid-hint::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            background: rgba(16, 185, 129, 0.4);
            border: 2px solid #10b981;
            border-radius: 50%;
            z-index: 5;
            pointer-events: none;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
        }

        .coordinate {
            position: absolute; 
            font-size: 9px; 
            font-weight: 700; 
            opacity: 0.35; 
            pointer-events: none; 
            user-select: none; 
        }
        .light-sq .coordinate { color: var(--board-dark); }
        .dark-sq .coordinate { color: var(--board-light); }
        .rank-label { left: 5px; top: 5px; } 
        .file-label { right: 5px; bottom: 5px; } 

        /* ---------------- MEDIA QUERIES (RESPONSIVENESS) ---------------- */
        @media (max-width: 992px) {
            .mobile-navbar {
                display: flex; 
            }

            .page-wrapper > :first-child {
                display: none !important; 
            }

            .main-content {
                margin-left: 0; 
                padding-top: 90px; 
                width: 100%;
            }

            .game-area {
                max-width: 88vw; 
                max-height: 88vw;
            }
        }

        @media (max-width: 480px) {
            h2 { letter-spacing: 1px; } 
            .info-panel { gap: 20px; padding: 10px 25px; }
            .stat-value { font-size: 20px; }
            .coordinate { font-size: 8px; }
        }
    </style>
</head>
<body>

<div class="page-wrapper">
    <!-- Desktop Sidebar Inclusion -->
    <?php include 'st_sidebar.php'; ?> 

    <!-- Mobile Top Navigation Bar -->
    <div class="mobile-navbar">
        <span style="font-family: 'Cinzel', serif; font-weight: 900; color: #fff; letter-spacing: 1px; background: linear-gradient(to right, #fff, var(--gold)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">GRANDMASTER</span>
        <button class="menu-toggle-btn" onclick="toggleMobileSidebar()">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <!-- Sliding Sidebar Overlay for Mobile -->
    <div id="mobileSidebarOverlay" style="position: fixed; top: 0; left: -280px; width: 250px; height: 100vh; background: #0c0914; z-index: 2000; transition: left 0.3s ease; border-right: 1px solid var(--glass-border); overflow-y: auto;">
        <div style="padding: 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--glass-border);">
            <span style="color: var(--gold); font-weight: 700; font-family: 'Cinzel', serif;">MENU</span>
            <i class="fa-solid fa-xmark" style="cursor:pointer; font-size: 1.2rem; color: #fff;" onclick="toggleMobileSidebar()"></i>
        </div>
        <div>
            <?php @include 'st_sidebar.php'; ?>
        </div>
    </div>
    <div id="sidebarBackdrop" style="position: fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); backdrop-filter: blur(3px); z-index:1999; display:none;" onclick="toggleMobileSidebar()"></div>

    <div class="main-content">
        <h2>♔ CHESS GRANDMASTER</h2> 

        <div class="info-panel"> 
            <div class="stat-box"> 
                <div class="stat-label">Timer</div> 
                <div id="timer" class="stat-value">00:00</div> 
            </div>
            <div class="stat-box"> 
                <div class="stat-label">Score</div> 
                <div id="score" class="stat-value">0</div> 
            </div>
        </div>

        <div id="status">Your Turn (White)</div> 

        <div class="game-area"> 
            <div class="main-container" id="board"></div> 
        </div>
    </div>
</div>

<script>
// Mobile Sidebar Logic
let sidebarOpen = false;
function toggleMobileSidebar() {
    const sidebar = document.getElementById('mobileSidebarOverlay');
    const backdrop = document.getElementById('sidebarBackdrop');
    if(!sidebarOpen) {
        sidebar.style.left = '0px';
        backdrop.style.display = 'block';
        sidebarOpen = true;
    } else {
        sidebar.style.left = '-280px';
        backdrop.style.display = 'none';
        sidebarOpen = false;
    }
}

const boardEl = document.getElementById('board'); 
let selectedSquare = null; 
let isPlayerTurn = true;  
let score = 0; 
let seconds = 0; 

let initialBoard = [ 
    ['b1','b2','b3','b4','b5','b3','b2','b1'],
    ['b6','b6','b6','b6','b6','b6','b6','b6'],
    ['','','','','','','',''],
    ['','','','','','','',''],
    ['','','','','','','',''],
    ['','','','','','','',''],
    ['w6','w6','w6','w6','w6','w6','w6','w6'],
    ['w1','w2','w3','w4','w5','w3','w2','w1']
];

const files = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h']; 

function createBoard() {
    boardEl.innerHTML = ""; 
    for (let r = 0; r < 8; r++) {
        for (let c = 0; c < 8; c++) {
            let sq = document.createElement('div'); 
            sq.className = `square ${(r + c) % 2 === 0 ? 'light-sq' : 'dark-sq'}`; 
            sq.dataset.row = r; 
            sq.dataset.col = c; 

            if (c === 0) {
                let rankLabel = document.createElement('span'); 
                rankLabel.className = 'coordinate rank-label'; 
                rankLabel.innerText = 8 - r; 
                sq.appendChild(rankLabel); 
            }
            if (r === 7) {
                let fileLabel = document.createElement('span'); 
                fileLabel.className = 'coordinate file-label'; 
                fileLabel.innerText = files[c]; 
                sq.appendChild(fileLabel); 
            }

            let piece = initialBoard[r][c]; 
            if (piece) {
                let img = document.createElement('img'); 
                let folder = piece.startsWith('w') ? 'white' : 'black'; 
                let num = piece.substring(1); 
                img.src = `images/${folder}/${num}.png`; 
                sq.appendChild(img); 
            }
            sq.onclick = () => handleSquareClick(sq); 
            boardEl.appendChild(sq); 
        }
    }
}

// Move Hints පෙන්වීමට එකතු කළ කොටස
function removeHints() {
    document.querySelectorAll('.square').forEach(sq => sq.classList.remove('valid-hint'));
}

function showHints(fromR, fromC, movingPiece) {
    document.querySelectorAll('.square').forEach(sq => {
        let toR = parseInt(sq.dataset.row);
        let toC = parseInt(sq.dataset.col);
        if (validateMove(movingPiece, fromR, fromC, toR, toC)) {
            sq.classList.add('valid-hint');
        }
    });
}

setInterval(() => {
    seconds++; 
    let mins = Math.floor(seconds / 60); 
    let secs = seconds % 60; 
    document.getElementById('timer').innerText = `${mins.toString().padStart(2,'0')}:${secs.toString().padStart(2,'0')}`; 
}, 1000); 

function handleSquareClick(sq) {
    if (!isPlayerTurn) return; 

    let r = parseInt(sq.dataset.row); 
    let c = parseInt(sq.dataset.col); 
    let piece = initialBoard[r][c]; 

    if (selectedSquare) {
        let fromR = parseInt(selectedSquare.dataset.row); 
        let fromC = parseInt(selectedSquare.dataset.col); 
        let movingPiece = initialBoard[fromR][fromC]; 

        if (piece && piece.startsWith('w')) {
            selectedSquare.classList.remove('selected'); 
            removeHints();
            selectedSquare = sq; 
            sq.classList.add('selected'); 
            showHints(r, c, piece);
            return;
        }

        if (validateMove(movingPiece, fromR, fromC, r, c)) {
            executeMove(fromR, fromC, r, c); 
        } else {
            selectedSquare.classList.remove('selected'); 
            removeHints();
            selectedSquare = null; 
        }
    } else {
        if (piece && piece.startsWith('w')) {
            selectedSquare = sq; 
            sq.classList.add('selected'); 
            showHints(r, c, piece);
        }
    }
}

function validateMove(piece, fromR, fromC, toR, toC) {
    if (fromR === toR && fromC === toC) return false; 
    
    let type = piece.substring(1); 
    let isWhite = piece.startsWith('w'); 
    let target = initialBoard[toR][toC]; 

    if (target && target.startsWith(isWhite ? 'w' : 'b')) return false; 

    let dr = toR - fromR; 
    let dc = toC - fromC; 
    let adr = Math.abs(dr); 
    let adc = Math.abs(dc); 

    switch(type) {
        case '6': 
            let dir = isWhite ? -1 : 1; 
            let startRow = isWhite ? 6 : 1; 
            if (dc === 0 && !target) {
                if (dr === dir) return true; 
                if (fromR === startRow && dr === 2 * dir && !initialBoard[fromR + dir][fromC]) return true; 
            }
            if (adc === 1 && dr === dir && target) return true; 
            return false;

        case '1': 
            if (dr !== 0 && dc !== 0) return false; 
            return isPathClear(fromR, fromC, toR, toC); 

        case '2': 
            return (adr === 2 && adc === 1) || (adr === 1 && adc === 2); 

        case '3': 
            if (adr !== adc) return false; 
            return isPathClear(fromR, fromC, toR, toC); 

        case '4': 
            if (dr !== 0 && dc !== 0 && adr !== adc) return false; 
            return isPathClear(fromR, fromC, toR, toC); 

        case '5': 
            return adr <= 1 && adc <= 1; 
    }
    return false;
}

function isPathClear(fromR, fromC, toR, toC) {
    let stepR = Math.sign(toR - fromR); 
    let stepC = Math.sign(toC - fromC); 
    let currR = fromR + stepR; 
    let currC = fromC + stepC; 

    while (currR !== toR || currC !== toC) {
        if (initialBoard[currR][currC] !== '') return false; 
        currR += stepR; 
        currC += stepC; 
    }
    return true; 
}

function updateStatus(text, colorBg, colorText) {
    const statusEl = document.getElementById("status"); 
    statusEl.innerText = text; 
    statusEl.style.background = colorBg; 
    statusEl.style.borderColor = colorText; 
    statusEl.style.color = colorText; 
}

function executeMove(fromR, fromC, toR, toC) {
    let movingPiece = initialBoard[fromR][fromC]; 
    let target = initialBoard[toR][toC]; 

    removeHints();

    if (target && target.substring(1) === '5') {
        updateStatus("YOU WIN! 🏆", "rgba(16, 185, 129, 0.2)", "var(--neon-green)"); 
        celebrate(); 
        isPlayerTurn = false; 
        return;
    }

    if (target) score += 100; 
    document.getElementById('score').innerText = score; 

    initialBoard[toR][toC] = movingPiece; 
    initialBoard[fromR][fromC] = ''; 

    selectedSquare.classList.remove('selected'); 
    selectedSquare = null; 

    createBoard(); 

    isPlayerTurn = false; 
    updateStatus("AI is Thinking...", "rgba(239, 68, 68, 0.1)", "var(--neon-red)"); 
    setTimeout(computerMove, 800); 
}

function computerMove() {
    let aiPieces = []; 
    for (let r = 0; r < 8; r++) {
        for (let c = 0; c < 8; c++) {
            if (initialBoard[r][c].startsWith('b')) {
                aiPieces.push({ r, c, piece: initialBoard[r][c] }); 
            }
        }
    }

    if (aiPieces.length === 0) return; 

    let validMoves = []; 
    for (let p of aiPieces) {
        for (let r = 0; r < 8; r++) {
            for (let c = 0; c < 8; c++) {
                if (validateMove(p.piece, p.r, p.c, r, c)) {
                    validMoves.push({ from: p, to: { r, c } }); 
                }
            }
        }
    }

    if (validMoves.length > 0) {
        let captures = validMoves.filter(m => initialBoard[m.to.r][m.to.c] !== ''); 
        let chosenMove = captures.length > 0 ? captures[Math.floor(Math.random() * captures.length)] : validMoves[Math.floor(Math.random() * validMoves.length)]; 

        let target = initialBoard[chosenMove.to.r][chosenMove.to.c]; 
        if (target && target.substring(1) === '5') {
            alert("Game Over! AI Wins."); 
            location.reload(); 
            return;
        }

        initialBoard[chosenMove.to.r][chosenMove.to.c] = chosenMove.from.piece; 
        initialBoard[chosenMove.from.r][chosenMove.from.c] = ''; 
    }

    createBoard(); 
    isPlayerTurn = true; 
    updateStatus("Your Turn (White)", "rgba(16, 185, 129, 0.1)", "var(--neon-green)"); 
}

function celebrate() {
    let end = Date.now() + (4 * 1000); 
    (function frame() {
        confetti({ particleCount: 3, angle: 60, spread: 55, origin: { x: 0 } }); 
        confetti({ particleCount: 3, angle: 120, spread: 55, origin: { x: 1 } }); 
        if (Date.now() < end) { requestAnimationFrame(frame); } 
    }());
}

createBoard(); 
</script>
</body>
</html>
<?php
// Output Buffer එක අවසන් කර දත්ත පිටතට යැවීම
ob_end_flush(); 
?>