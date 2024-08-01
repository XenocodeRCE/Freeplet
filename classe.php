<?php

$classId = $_GET['id'];

// Lire les classes depuis le fichier JSON
function getClasses() {
    $json = file_get_contents('classes.json');
    return json_decode($json, true);
}

// Lire les questions depuis le fichier JSON
function getQuestions() {
    $json = file_get_contents('questions.json');
    return json_decode($json, true);
}

// Sauvegarder les questions dans le fichier JSON
function saveQuestions($questions) {
    $json = json_encode($questions, JSON_PRETTY_PRINT);
    file_put_contents('questions.json', $json);
}

$classes = getClasses();
$questions = getQuestions();

$class = array_filter($classes, function($class) use ($classId) {
    return $class['id'] == $classId;
});

if (empty($class)) {
    die("Classe non trouvée");
}

$class = array_values($class)[0];

// Ajouter une nouvelle question
if (isset($_POST['addQuestion'])) {
    if (!isset($questions[$classId])) {
        $questions[$classId] = array();
    }
    $newQuestion = [
        'id' => count($questions[$classId]) + 1,
        'text' => $_POST['questionText'],
        'type' => $_POST['questionType']
    ];
    $questions[$classId][] = $newQuestion;
    saveQuestions($questions);
}

// Supprimer une question
if (isset($_GET['deleteQuestion'])) {
    $questionId = $_GET['deleteQuestion'];
    $questions[$classId] = array_filter($questions[$classId], function($question) use ($questionId) {
        return $question['id'] != $questionId;
    });
    saveQuestions($questions);
}

if (isset($_GET['deleteQuestion'])) {
    $url = "https://lacavernedeplaton.fr/ticket/classe.php?id=$classId";
    echo "<script type='text/javascript'>window.location = '$url'</script>";
}
if (isset($_GET['addQuestion'])) {
    $url = "https://lacavernedeplaton.fr/ticket/classe.php?id=$classId";
    echo "<script type='text/javascript'>window.location = '$url'</script>";
}

$classQuestions = isset($questions[$classId]) ? $questions[$classId] : array();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de la Classe</title>
    <style>
        /* Ajouter du style pour une meilleure présentation */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: auto;
        }
        h2 {
            margin-top: 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        .form-group textarea {
            height: 100px;
        }
        .form-group button {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .form-group button:hover {
            background-color: #45a049;
        }
        .question-list {
            margin-top: 20px;
        }
        .question-item {
            padding: 10px;
            background-color: #e9e9e9;
            border-radius: 4px;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .question-item button {
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            padding: 5px 10px;
        }
        .question-item button:hover {
            background-color: #e53935;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Gestion de la Classe: <?php echo htmlspecialchars($class['name']); ?></h2>
        <h2>PIN: <?php echo htmlspecialchars($class['pin']); ?></h2>
        <form method="post">
            <div class="form-group">
                <label for="questionText">Texte de la question</label>
                <textarea id="questionText" name="questionText" required></textarea>
            </div>
            <div class="form-group">
                <label for="questionType">Type de question</label>
                <select id="questionType" name="questionType" required>
                    <option value="text">Réponse écrite</option>
                    <option value="vote">Vote (0 à 10)</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" name="addQuestion">Ajouter la question</button>
            </div>
        </form>
        <div class="question-list">
            <?php foreach ($classQuestions as $question): ?>
                <div class="question-item">
                    <?php echo htmlspecialchars($question['text']); ?> (<?php echo $question['type'] == 'vote' ? 'Vote (0-10)' : 'Réponse écrite'; ?>)
                    <button onclick="window.location.href='?id=<?php echo $classId; ?>&deleteQuestion=<?php echo $question['id']; ?>'">Supprimer</button>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="form-group">
            <label>Lien d'invitation :</label>
            <input type="text" value="https://lacavernedeplaton.fr/ticket/ticket.php?classe=<?php echo $classId; ?>" readonly>
        </div>
    </div>
</body>
</html>