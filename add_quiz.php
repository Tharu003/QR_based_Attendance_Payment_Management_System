<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

// පරීක්ෂණ කටයුතු සඳහා Session නැත්නම් mock එකක් (Real environment එකේදී මෙය ඉවත් කරන්න)
if(!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'teacher'; 
}

$allowed_roles = ['admin', 'teacher'];
if(!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)){
    header("Location: dashboard.php?error=unauthorized");
    exit();
}

/* ================= SAVE QUESTIONS / GOOGLE FORM ================= */
if(isset($_POST['save_all_questions'])){
    $material_id = intval($_POST['material_id']);
    $creation_mode = isset($_POST['creation_mode']) ? mysqli_real_escape_string($conn, $_POST['creation_mode']) : 'manual';

    if(!$material_id){
        echo "<script>alert('Please select a target quiz lesson first!'); window.history.back();</script>";
        exit();
    }

    // Google Form Saving Node
    if($creation_mode === 'google_form'){
        $gform_url = isset($_POST['gform_url']) ? mysqli_real_escape_string($conn, $_POST['gform_url']) : '';
        if(empty($gform_url)){
            echo "<script>alert('Please enter a valid Google Form URL!'); window.history.back();</script>";
            exit();
        }
        
        $stmt = $conn->prepare("INSERT INTO quiz_questions (material_id, question, option_a, option_b, option_c, option_d, correct_option, generated_by) VALUES (?, ?, '', '', '', '', 'GFORM', 'google_form')");
        $stmt->bind_param("is", $material_id, $gform_url);
        $stmt->execute();
        
        echo "<script>alert('✅ Google Form Quiz Linked Successfully!'); window.location='add_quiz.php';</script>";
        exit();
    }

    // AI or Manual Saving Node
    if(isset($_POST['questions']) && is_array($_POST['questions'])){
        $stmt = $conn->prepare("
            INSERT INTO quiz_questions
            (material_id, question, option_a, option_b, option_c, option_d, correct_option, generated_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $count = 0;
        foreach($_POST['questions'] as $q){
            if(empty(trim($q['text']))) continue;

            $question = $q['text'];
            $a = $q['a'];
            $b = $q['b'];
            $c = $q['c'];
            $d = $q['d'];
            $correct = $q['correct'];

            $stmt->bind_param("isssssss", $material_id, $question, $a, $b, $c, $d, $correct, $creation_mode);
            if($stmt->execute()){ $count++; }
        }
        echo "<script>alert('✅ Successfully published quiz with $count questions!'); window.location='add_quiz.php';</script>";
        exit();
    }
}

/* ================= FETCH ALL MATERIALS ================= */
// සියලුම Materials ලබා ගැනීම සඳහා WHERE material_type='quiz' ඉවත් කර ඇත
$mats = mysqli_query($conn,"SELECT id, title, week_no, material_type FROM class_materials ORDER BY week_no ASC, id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Portal | Ultimate Quiz Creator Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Noto+Serif+Sinhala:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
       :root { 
            --bg-soft: #0b0f19;
            --card-glass: rgba(22, 28, 45, 0.65);
            --border-glow: rgba(255, 255, 255, 0.06);
            --gold-glow: #eab308;
            --mint-green: #2dd4bf;
            --soft-blue: #60a5fa;
            --purple-glow: #a855f7;
        }

        body { 
            background-color: var(--bg-soft); 
            color: #e4e4e7;
            font-family: 'Plus Jakarta Sans', sans-serif; 
            min-height: 100vh;
            background-image: radial-gradient(circle at 50% 0%, rgba(96, 165, 250, 0.05) 0%, transparent 50%),
                              radial-gradient(circle at 50% 100%, rgba(45, 212, 191, 0.03) 0%, transparent 50%);
            background-attachment: fixed;
            overflow-x: hidden;
        }

        /* Responsive Sidebar Setup */
        .main-content { 
            margin-left: 280px; 
            padding: 2.5rem 1.5rem; 
            transition: all 0.3s ease;
        }

        /* Elegant Glass Card */
        .glass-card { 
            background: var(--card-glass); 
            border-radius: 24px; 
            border: 1px solid var(--border-glow); 
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3); 
            backdrop-filter: blur(20px); 
            transition: all 0.4s ease;
        }

        /* Romantic Poetry Banner */
        .poetry-banner {
            background: linear-gradient(to right, rgba(11, 15, 25, 0.9) 30%, rgba(11, 15, 25, 0.4)), url('https://images.unsplash.com/photo-1516414447565-b14be0adf13e?q=80&w=1200&auto=format&fit=crop');
            background-size: cover; 
            background-position: center; 
            border-radius: 24px; 
            border: 1px solid rgba(255,255,255,0.05);
            box-shadow: inset 0 0 100px rgba(0,0,0,0.8);
        }
        .poetry-title { font-family: 'Noto Serif Sinhala', serif; font-weight: 700; font-size: 2.3rem; color: #ffffff; }
        .poetry-line { font-family: 'Noto Serif Sinhala', serif; font-style: italic; color: #94a3b8; font-size: 1.05rem; }

        /* Mode Option Cards */
        .mode-card {
            border-radius: 24px; 
            overflow: hidden; 
            border: 1px solid var(--border-glow);
            background: rgba(15, 23, 42, 0.6); 
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            cursor: pointer; 
            position: relative; 
            height: 100%;
        }
        .mode-card .img-wrapper { height: 150px; position: relative; overflow: hidden; }
        .mode-card .img-wrapper img {
            width: 100%; height: 100%; object-fit: cover; transition: transform 0.8s ease; filter: brightness(0.65);
        }
        .mode-card:hover {
            transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.5); border-color: rgba(255,255,255,0.1);
        }
        .mode-card:hover .img-wrapper img { transform: scale(1.08); filter: brightness(0.8); }
        
        /* Interactive Selections */
        .mode-card.active-ai { border-color: #a855f7; box-shadow: 0 0 30px rgba(168, 85, 247, 0.2); }
        .mode-card.active-ai .mode-tag { background: linear-gradient(135deg, #a855f7, #6366f1); }
        
        .mode-card.active-manual { border-color: var(--soft-blue); box-shadow: 0 0 30px rgba(96, 165, 250, 0.2); }
        .mode-card.active-manual .mode-tag { background: linear-gradient(135deg, #3b82f6, #06b6d4); }
        
        .mode-card.active-gform { border-color: var(--mint-green); box-shadow: 0 0 30px rgba(45, 212, 191, 0.2); }
        .mode-card.active-gform .mode-tag { background: linear-gradient(135deg, #0d9488, #2dd4bf); }

        .mode-tag {
            position: absolute; top: 15px; left: 15px; padding: 0.35rem 1rem; border-radius: 30px;
            font-size: 0.75rem; font-weight: 600; background: rgba(0,0,0,0.6); color: #f3f4f6; backdrop-filter: blur(5px);
        }

        /* Input Controls */
        .form-control, .form-select, .input-group-text { 
            border-radius: 14px; padding: 0.85rem 1.2rem; border: 1px solid var(--border-glow); background: rgba(15, 23, 42, 0.8); color: #f4f4f5;
        }
        .input-group-text { background: rgba(255,255,255,0.03); color: var(--soft-blue); font-weight: bold; min-width: 45px; justify-content: center; }
        .form-control:focus, .form-select:focus { background: #0f172a; color: white; border-color: var(--soft-blue); box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.15); }

        /* Question Blocks */
        .question-block {
            background: rgba(20, 30, 54, 0.5); border-radius: 22px; border: 1px solid var(--border-glow); padding: 30px; margin-bottom: 30px; position: relative;
            animation: slideUp 0.4s ease-out;
        }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .btn-save-master {
            background: linear-gradient(135deg, #0d9488 0%, #115e59 100%); color: white; border: none; padding: 1.2rem; border-radius: 18px; font-weight: 700; font-size: 1.1rem; box-shadow: 0 15px 30px -5px rgba(13, 148, 136, 0.3); transition: all 0.3s;
        }
        .btn-save-master:hover { transform: translateY(-2px); box-shadow: 0 20px 40px -5px rgba(13, 148, 136, 0.5); }

        .delete-q-btn {
            position: absolute; top: 20px; right: 20px; background: rgba(239, 68, 68, 0.08); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.15); border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; transition: all 0.3s;
        }
        .delete-q-btn:hover { background: #ef4444; color: white; }
        .text-purple { color: var(--purple-glow) !important; }

        /* Tablet සහ Large Screens වෙනස්කම් */
        @media (max-width: 1200px) {
            .main-content { padding: 2rem 1rem; }
            .poetry-title { font-size: 2rem; }
        }

        /* Mobile Responsive Viewports (ප්‍රධාන වෙනස්කම්) */
        @media (max-width: 992px) { 
            .main-content { margin-left: 0; padding: 1.5rem 1rem; } 
            .poetry-banner { text-align: center; justify-content: center; }
            .poetry-title { font-size: 1.8rem; }
            .poetry-line { font-size: 0.95rem; }
            .question-block { padding: 20px; }
            .delete-q-btn { top: 15px; right: 15px; width: 30px; height: 30px; font-size: 0.85rem; }
        }
        
        @media (max-width: 576px) {
            .poetry-title { font-size: 1.5rem; }
            .glass-card { padding: 1.5rem !important; border-radius: 16px; }
            .question-block { padding: 15px; border-radius: 16px; }
            .form-control, .form-select { padding: 0.75rem 1rem; font-size: 0.9rem; }
            .input-group-text { padding: 0.75rem; font-size: 0.9rem; }
        }
    </style>
</head>
<body>

    <?php if(file_exists('sidebar.php')) { include 'sidebar.php'; } ?>

    <main class="main-content">
        <div class="container-fluid px-0">
            <div class="row justify-content-center mx-0">
                <div class="col-xl-10 col-lg-12 px-0">
                    
                    <!-- Title Banner -->
                    <div class="poetry-banner p-4 p-md-5 mb-4 mb-md-5 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
                        <div>
                            <h2 class="poetry-title mb-2">Quiz Generator</h2>
                            <p class="poetry-line mb-0">"අකුරු කරන්නට සිසු සිත් පහන් කරලන, සොඳුරු අත්දැකීමක් මතින් දැනුම මැන බලන්න..."</p>
                        </div>
                        <div class="text-md-end">
                            <a href="materials.php" class="btn btn-outline-light rounded-pill px-4 py-2 text-white border-opacity-50 w-100 w-md-auto">Back to Directory</a>
                        </div>
                    </div>

                    <!-- Step 1 Select Box -->
                    <div class="glass-card p-4 p-md-5 mb-4">
                        <label class="form-label text-white fw-bold mb-3" style="font-size: 1.05rem; letter-spacing: 0.5px;">
                            <span class="text-warning me-2"><i class="fas fa-bookmark"></i></span> STEP 1: SELECT TARGET MATERIAL
                        </label>
                        <select id="global_material_id" class="form-select form-select-lg fs-6" required>
                            <option value="">-- Choose target material from directory --</option>
                            <?php if($mats && mysqli_num_rows($mats) > 0): 
                                    while($row = mysqli_fetch_assoc($mats)): ?>
                                        <option value="<?= $row['id'] ?>">Week <?= $row['week_no'] ?> : <?= htmlspecialchars($row['title']) ?> (<?= strtoupper($row['material_type']) ?>)</option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>

                    <!-- Mode Select Grid -->
                    <div class="row g-4 mb-4 mb-md-5">
                        <div class="col-md-4">
                            <div id="choose_ai_btn" class="mode-card">
                                <div class="img-wrapper">
                                    <img src="https://images.unsplash.com/photo-1614064641938-3bbee52942c7?q=80&w=500&auto=format&fit=crop" alt="AI Context">
                                    <span class="mode-tag"><i class="fas fa-magic me-1"></i> AI Engine</span>
                                </div>
                                <div class="p-4">
                                    <h5 class="text-white fw-bold mb-2">AI Cybernetic</h5>
                                    <p class="text-gray small mb-0">Upload a reference PDF to instantly auto-synthesize evaluation MCQs.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div id="choose_manual_btn" class="mode-card">
                                <div class="img-wrapper">
                                    <img src="https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?q=80&w=500&auto=format&fit=crop" alt="Manual Draft">
                                    <span class="mode-tag"><i class="fas fa-pen me-1"></i> Manual</span>
                                </div>
                                <div class="p-4">
                                    <h5 class="text-white fw-bold mb-2">Manual Organic</h5>
                                    <p class="text-gray small mb-0">Hand-craft custom questions meticulously option by option.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div id="choose_gform_btn" class="mode-card">
                                <div class="img-wrapper">
                                    <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=500&auto=format&fit=crop" alt="Google Cloud">
                                    <span class="mode-tag"><i class="fab fa-google me-1"></i> Google Form</span>
                                </div>
                                <div class="p-4">
                                    <h5 class="text-white fw-bold mb-2">Google Form Link</h5>
                                    <p class="text-gray small mb-0">Embed an external live Google Form assessment frame directly for students.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Upload Box -->
                    <div id="ai_upload_box" class="glass-card p-4 p-md-5 mb-4 mb-md-5 d-none">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="p-3 rounded-3 bg-dark border border-secondary d-none d-sm-block"><i class="fas fa-wand-magic-sparkles text-warning fs-4"></i></div>
                            <div>
                                <h4 class="text-white mb-0 fw-bold fs-5 fs-sm-4">Neural Engine Workspace</h4>
                                <p class="text-muted small mb-0">Provide context material below for the generation process.</p>
                            </div>
                        </div>
                        <div class="row align-items-end g-3">
                            <div class="col-md-8">
                                <label class="form-label text-muted small fw-bold"><i class="fas fa-file-pdf me-1"></i> Stream Reference PDF Node</label>
                                <input type="file" id="pdf_file" class="form-control" accept=".pdf">
                            </div>
                            <div class="col-md-4">
                                <button type="button" id="run_ai_generator" class="btn text-white w-100 py-3 fw-bold shadow-lg" style="background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%); border:none; border-radius:14px;">
                                    <i class="fas fa-sparkles me-2"></i> Ignite AI Generator
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Loading -->
                    <div id="loading_section" class="text-center my-5 d-none">
                        <div class="spinner-border text-warning mb-3" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-white fw-bold mb-1">AI Core compiling evaluation items...</p>
                        <p class="text-muted small">Generating premium questions. Please hold.</p>
                    </div>

                    <!-- Main Quiz Dynamic Form -->
                    <form method="POST" id="main_quiz_form" class="d-none">
                        <input type="hidden" name="material_id" id="final_material_id">
                        <input type="hidden" name="creation_mode" id="final_creation_mode" value="manual">

                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
                            <h3 id="form_title_display" class="text-white fw-bold mb-0 fs-5 fs-sm-4">Compilation Area</h3>
                            <button type="button" id="add_manual_field_btn" class="btn btn-info rounded-pill px-4 py-2 text-white fw-bold d-none w-100 w-sm-auto" style="background:linear-gradient(135deg, #3b82f6, #06b6d4); border:none;">
                                <i class="fas fa-plus me-2"></i>Draft Next Question
                            </button>
                        </div>

                        <div id="questions_wrapper"></div>

                        <button type="submit" name="save_all_questions" class="btn btn-save-master w-100 mt-4 shadow-lg">
                            <i class="fas fa-check-circle me-2"></i> AUTHORIZE & PUBLISH QUIZ
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </main>

    <script>
        let questionCounter = 0;

        const globalMaterialSelect = document.getElementById('global_material_id');
        const finalMaterialInput = document.getElementById('final_material_id');
        const finalCreationModeInput = document.getElementById('final_creation_mode');
        
        const aiBtn = document.getElementById('choose_ai_btn');
        const manualBtn = document.getElementById('choose_manual_btn');
        const gformBtn = document.getElementById('choose_gform_btn');
        
        const aiUploadBox = document.getElementById('ai_upload_box');
        const mainQuizForm = document.getElementById('main_quiz_form');
        const questionsWrapper = document.getElementById('questions_wrapper');
        const formTitleDisplay = document.getElementById('form_title_display');
        const addManualFieldBtn = document.getElementById('add_manual_field_btn');
        const loadingSection = document.getElementById('loading_section');

        globalMaterialSelect.addEventListener('change', function() {
            finalMaterialInput.value = this.value;
        });

        function resetActiveClasses() {
            aiBtn.classList.remove('active-ai');
            manualBtn.classList.remove('active-manual');
            gformBtn.classList.remove('active-gform');
            aiUploadBox.classList.add('d-none');
            addManualFieldBtn.classList.add('d-none');
            mainQuizForm.classList.add('d-none');
            questionsWrapper.innerHTML = '';
            questionCounter = 0;
        }

        aiBtn.addEventListener('click', function() {
            if(!checkMaterialSelected()) return;
            resetActiveClasses();
            aiBtn.classList.add('active-ai');
            aiUploadBox.classList.remove('d-none');
            finalCreationModeInput.value = 'ai';
        });

        manualBtn.addEventListener('click', function() {
            if(!checkMaterialSelected()) return;
            resetActiveClasses();
            manualBtn.classList.add('active-manual');
            mainQuizForm.classList.remove('d-none');
            addManualFieldBtn.classList.remove('d-none');
            finalCreationModeInput.value = 'manual';
            formTitleDisplay.innerHTML = `<i class="fas fa-keyboard text-info me-2"></i> Custom Quiz Builder`;
            addNewQuestionBlock(false);
        });

        gformBtn.addEventListener('click', function() {
            if(!checkMaterialSelected()) return;
            resetActiveClasses();
            gformBtn.classList.add('active-gform');
            mainQuizForm.classList.remove('d-none');
            finalCreationModeInput.value = 'google_form';
            formTitleDisplay.innerHTML = `<i class="fab fa-google text-success me-2"></i> Google Forms Link`;
            
            questionsWrapper.innerHTML = `
                <div class="glass-card p-4 p-sm-5 text-center" style="border-color: rgba(45, 212, 191, 0.2);">
                    <i class="fab fa-google text-success display-4 mb-3"></i>
                    <h5 class="text-white mb-2 fw-bold">Paste your Google Form Shareable Link below</h5>
                    <p class="text-muted small mb-4">Students will be able to attempt this quiz via embedded frame directly.</p>
                    <div class="p-2 mx-auto" style="max-width: 600px;">
                        <input type="url" name="gform_url" class="form-control form-control-lg text-center text-wrap" placeholder="https://docs.google.com/forms/d/e/.../viewform" required style="border-color: rgba(45, 212, 191, 0.3); font-size:1rem;">
                    </div>
                </div>
            `;
        });

        document.getElementById('run_ai_generator').addEventListener('click', function() {
            const pdfFile = document.getElementById('pdf_file').files[0];
            if(!pdfFile) { alert('Please mount a reference document source node (PDF)!'); return; }

            loadingSection.classList.remove('d-none');
            mainQuizForm.classList.add('d-none');

            let formData = new FormData();
            formData.append('pdf_file', pdfFile);

            fetch('generate_ai_quiz.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response error');
                return response.json();
            })
            .then(questions => {
                loadingSection.classList.add('d-none');
                if(questions.error) { alert('Error: ' + questions.error); return; }

                mainQuizForm.classList.remove('d-none');
                formTitleDisplay.innerHTML = `<i class="fas fa-microchip text-purple me-2"></i> Synthesized AI Assessment Layout (${questions.length})`;
                
                questionsWrapper.innerHTML = ''; 
                questionCounter = 0;

                questions.forEach((q) => {
                    questionCounter++;
                    const block = document.createElement('div');
                    block.className = 'question-block';
                    block.id = `q_block_${questionCounter}`;
                    
                    block.innerHTML = `
                        <button type="button" class="delete-q-btn" onclick="removeQuestionBlock(${questionCounter})"><i class="fas fa-times"></i></button>
                        <div class="mb-3">
                            <label class="form-label text-purple fw-bold small">
                                <i class="fas fa-robot me-1"></i> QUESTION COMPONENT #${questionCounter}
                            </label>
                            <textarea name="questions[${questionCounter}][text]" class="form-control" rows="2" required>${q.text}</textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6"><div class="input-group"><span class="input-group-text">A</span><input type="text" name="questions[${questionCounter}][a]" class="form-control" required value="${q.a}"></div></div>
                            <div class="col-md-6"><div class="input-group"><span class="input-group-text">B</span><input type="text" name="questions[${questionCounter}][b]" class="form-control" required value="${q.b}"></div></div>
                            <div class="col-md-6"><div class="input-group"><span class="input-group-text">C</span><input type="text" name="questions[${questionCounter}][c]" class="form-control" required value="${q.c}"></div></div>
                            <div class="col-md-6"><div class="input-group"><span class="input-group-text">D</span><input type="text" name="questions[${questionCounter}][d]" class="form-control" required value="${q.d}"></div></div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-sm-6 col-md-4">
                                <label class="form-label text-success small fw-bold"><i class="fas fa-shield-check me-1"></i> LOCK CORRECT KEY</label>
                                <select name="questions[${questionCounter}][correct]" class="form-select form-select-sm" required>
                                    <option value="A" ${q.correct === 'A' ? 'selected' : ''}>Option A</option>
                                    <option value="B" ${q.correct === 'B' ? 'selected' : ''}>Option B</option>
                                    <option value="C" ${q.correct === 'C' ? 'selected' : ''}>Option C</option>
                                    <option value="D" ${q.correct === 'D' ? 'selected' : ''}>Option D</option>
                                </select>
                            </div>
                        </div>
                    `;
                    questionsWrapper.appendChild(block);
                });
                mainQuizForm.scrollIntoView({ behavior: 'smooth' });
            })
            .catch(error => {
                loadingSection.classList.add('d-none');
                alert('Error generating quiz.');
                console.error(error);
            });
        });

        addManualFieldBtn.addEventListener('click', () => addNewQuestionBlock(false));

        function addNewQuestionBlock(isAiGenerated, defaultText = '') {
            questionCounter++;
            const block = document.createElement('div');
            block.className = 'question-block';
            block.id = `q_block_${questionCounter}`;
            
            const textValue = defaultText || '';
            const optA = isAiGenerated ? "AI Option A" : "";
            const optB = isAiGenerated ? "AI Option B" : "";
            const optC = isAiGenerated ? "AI Option C" : "";
            const optD = isAiGenerated ? "AI Option D" : "";

            block.innerHTML = `
                <button type="button" class="delete-q-btn" onclick="removeQuestionBlock(${questionCounter})"><i class="fas fa-times"></i></button>
                <div class="mb-3 mb-md-4">
                    <label class="form-label fw-bold ${isAiGenerated ? 'text-warning' : 'text-info'} small">
                        <i class="${isAiGenerated ? 'fas fa-magic' : 'fas fa-pen-fancy'} me-1"></i> QUESTION COMPONENT #${questionCounter}
                    </label>
                    <textarea name="questions[${questionCounter}][text]" class="form-control" rows="2" placeholder="Start typing the core query parameter..." required>${textValue}</textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-6"><div class="input-group"><span class="input-group-text">A</span><input type="text" name="questions[${questionCounter}][a]" class="form-control" placeholder="Option A" required value="${optA}"></div></div>
                    <div class="col-md-6"><div class="input-group"><span class="input-group-text">B</span><input type="text" name="questions[${questionCounter}][b]" class="form-control" placeholder="Option B" required value="${optB}"></div></div>
                    <div class="col-md-6"><div class="input-group"><span class="input-group-text">C</span><input type="text" name="questions[${questionCounter}][c]" class="form-control" placeholder="Option C" required value="${optC}"></div></div>
                    <div class="col-md-6"><div class="input-group"><span class="input-group-text">D</span><input type="text" name="questions[${questionCounter}][d]" class="form-control" placeholder="Option D" required value="${optD}"></div></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-6 col-md-4">
                        <label class="form-label text-success small fw-bold"><i class="fas fa-check me-1"></i> LOCK CORRECT KEY</label>
                        <select name="questions[${questionCounter}][correct]" class="form-select form-select-sm" required>
                            <option value="A" ${questionCounter % 4 == 1 ? 'selected' : ''}>Option A</option>
                            <option value="B" ${questionCounter % 4 == 2 ? 'selected' : ''}>Option B</option>
                            <option value="C" ${questionCounter % 4 == 3 ? 'selected' : ''}>Option C</option>
                            <option value="D" ${questionCounter % 4 == 0 ? 'selected' : ''}>Option D</option>
                        </select>
                    </div>
                </div>
            `;
            questionsWrapper.appendChild(block);
        }

        function removeQuestionBlock(id) {
            const el = document.getElementById(`q_block_${id}`);
            if(el) el.remove();
        }

        function checkMaterialSelected() {
            if(!globalMaterialSelect.value) {
                alert('Please select a Target Material inside STEP 1 first!');
                globalMaterialSelect.focus();
                return false;
            }
            return true;
        }
    </script>
</body>
</html>