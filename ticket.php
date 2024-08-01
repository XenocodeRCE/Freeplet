<?php
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

// Lire les réponses depuis le fichier JSON
function getResponses() {
    $json = file_get_contents('responses.json');
    return json_decode($json, true);
}

// Sauvegarder les réponses dans le fichier JSON
function saveResponses($responses) {
    $json = json_encode($responses, JSON_PRETTY_PRINT);
    file_put_contents('responses.json', $json);
}

$classes = getClasses();
$questions = getQuestions();

$classId = $_GET['classe'];
$class = array_filter($classes, function($class) use ($classId) {
    return $class['id'] == $classId;
});

if (empty($class)) {
    die("Classe non trouvée");
}

$class = array_values($class)[0];
$classQuestions = isset($questions[$classId]) ? $questions[$classId] : [];
$responses = getResponses();

if (isset($_POST['submitResponse'])) {
    $studentName = $_POST['studentName'];
    $studentResponses = [];
    foreach ($classQuestions as $question) {
        $answer = $_POST['question-' . $question['id']];
        $studentResponses[] = [
            'questionId' => $question['id'],
            'answer' => $answer
        ];
    }
    if (!isset($responses[$classId])) {
        $responses[$classId] = [];
    }
    $responses[$classId][] = [
        'name' => $studentName,
        'answers' => $studentResponses
    ];
    saveResponses($responses);
    echo "<p>Merci pour vos réponses !</p>";
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Répondre aux Questions</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h2>Répondre aux Questions de la Classe: <?php echo htmlspecialchars($class['name']); ?></h2>
        <form method="post">
            <?php if (!isset($_POST['checkPin'])) : ?>
                <div class="form-group">
                    <label for="classPin">Entrer le code PIN</label>
                    <input type="text" id="classPin" name="classPin" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="checkPin">Vérifier le code PIN</button>
                </div>
            <?php elseif ($_POST['classPin'] === $class['pin']) : ?>
                <?php foreach ($classQuestions as $question) : ?>
                    <div class="form-group">
                        <label for="question-<?php echo $question['id']; ?>"><?php echo htmlspecialchars($question['text']); ?></label>
                        <?php if ($question['type'] === 'vote') : ?>
                            <select id="question-<?php echo $question['id']; ?>" name="question-<?php echo $question['id']; ?>" required>
                                <?php for ($i = 0; $i <= 10; $i++) : ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        <?php else : ?>
                            <textarea id="question-<?php echo $question['id']; ?>" name="question-<?php echo $question['id']; ?>" required></textarea>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <div class="form-group">
                    <label for="studentName">Votre nom</label>
                    <input type="text" id="studentName" name="studentName" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="submitResponse">Envoyer les réponses</button>
                </div>
            <?php else : ?>
                <p>Code PIN incorrect. Veuillez réessayer.</p>
                <div class="form-group">
                    <button type="button" onclick="window.location.href='ticket.php?classe=<?php echo $classId; ?>'">Réessayer</button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>