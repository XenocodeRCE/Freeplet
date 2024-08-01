<?php
session_start();

// Fonction pour lire les utilisateurs depuis le fichier JSON
function getUsers() {
    $json = file_get_contents('users.json');
    return json_decode($json, true);
}

// Fonction pour lire les classes depuis le fichier JSON
function getClasses() {
    $json = file_get_contents('classes.json');
    return json_decode($json, true);
}

// Fonction pour lire les questions depuis le fichier JSON
function getQuestions() {
    $json = file_get_contents('questions.json');
    return json_decode($json, true);
}

// Fonction pour lire les réponses depuis le fichier JSON
function getResponses() {
    $json = file_get_contents('responses.json');
    return json_decode($json, true);
}

// Fonction pour sauvegarder les questions dans le fichier JSON
function saveQuestions($questions) {
    $json = json_encode($questions, JSON_PRETTY_PRINT);
    file_put_contents('questions.json', $json);
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: classes.php');
    exit;
}

$user = $_SESSION['user'];
$classes = getClasses();
$questions = getQuestions();
$responses = getResponses();

$classId = $_GET['id'];
$class = array_filter($classes, function($class) use ($classId) {
    return $class['id'] == $classId;
});

if (empty($class)) {
    die("Classe non trouvée");
}

$class = array_values($class)[0];

// Vérifier si l'utilisateur a accès à cette classe
if (!in_array($class['id'], $user['classes'])) {
    die("Accès refusé");
}

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

$classQuestions = isset($questions[$classId]) ? $questions[$classId] : [];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de la Classe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: #4CAF50;
            color: white;
        }
        .header h2 {
            margin: 0;
        }
        .header .menu-button {
            background-color: white;
            color: #4CAF50;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            font-size: 24px;
            align-items: center;
            justify-content: center;
        }
        .header .menu-button:hover {
            background-color: #45a049;
        }
        .container {
            padding: 20px;
        }
        .grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .class-item {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            box-sizing: border-box;
        }
        .class-item h3 {
            margin-top: 0;
        }
        .class-item p {
            margin: 5px 0;
        }
        .class-item .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .class-item .actions button {
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
        }
        .class-item .actions button:hover {
            background-color: #45a049;
        }
        .class-item .actions .delete-button {
            background-color: #f44336;
        }
        .class-item .actions .delete-button:hover {
            background-color: #e53935;
        }
        /* Styles pour la modale */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            transition: opacity 0.3s ease;
            opacity: 0;
        }
        .modal.show {
            display: block;
            opacity: 1;
        }
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        /* Styles pour le message pop-up vert */
        .popup {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .popup.show {
            display: block;
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
        /* Styles pour la modale des suggestions */
        .suggestions-modal {
            display: none;
            position: fixed;
            z-index: 2;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            transition: opacity 0.3s ease;
            opacity: 0;
        }
        .suggestions-modal.show {
            display: block;
            opacity: 1;
        }
        .suggestions-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .suggestions-content ul {
            list-style-type: none;
            padding: 0;
        }
        .suggestions-content ul li {
            background-color: #f4f4f4;
            margin: 5px 0;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .suggestions-content ul li:hover {
            background-color: #ddd;
        }
    </style>
</head>
<body>

<div class="header">
    <h2><?php echo htmlspecialchars($class['name']); ?></h2>
    <div>
        <button class="menu-button" onclick="showAddQuestionModal()">+</button>
        <button class="menu-button" onclick="shareClass()">🔗</button>
    </div>
</div>
<div class="container">
    <div class="grid">
        <?php foreach ($classQuestions as $question): ?>
            <div class="class-item">
                <h3><?php echo htmlspecialchars($question['text']); ?></h3>
                <p>Type de question : <?php echo $question['type'] == 'vote' ? 'Vote (0-10)' : 'Réponse écrite'; ?></p>
                <div class="actions">
                    <button onclick="showResponsesModal(<?php echo $question['id']; ?>)">Voir les réponses</button>
                    <button class="delete-button" onclick="window.location.href='?id=<?php echo $classId; ?>&deleteQuestion=<?php echo $question['id']; ?>'">Supprimer</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modale pour créer une nouvelle question -->
<div id="addQuestionModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeAddQuestionModal()">&times;</span>
        <form method="post">
            <div class="form-group">
                <textarea id="questionText" name="questionText" placeholder="Texte de la question" required></textarea>
            </div>
            <div class="form-group">
                <label for="questionType">Type de question</label>
                <select id="questionType" name="questionType" required>
                    <option value="text">Réponse écrite</option>
                    <option value="vote">Vote (0 à 10)</option>
                </select>
            </div>
            <div class="form-group">
                <button type="button" onclick="showSuggestionsModal()">Suggestions</button>
            </div>
            <div class="form-group">
                <button type="submit" name="addQuestion">Ajouter la question</button>
            </div>
        </form>
    </div>
</div>

<!-- Pop-up vert pour indiquer que le lien est copié -->
<div id="popup" class="popup">
    Lien d'invitation copié !
</div>

<!-- Modale pour les suggestions de questions -->
<div id="suggestionsModal" class="suggestions-modal">
    <div class="suggestions-content">
        <span class="close" onclick="closeSuggestionsModal()">&times;</span>
        <ul>
            <li onclick="selectSuggestion('Comment avez-vous trouvé le cours d\'aujourd\'hui ?')">Comment avez-vous trouvé le cours d'aujourd'hui ?</li>
            <li onclick="selectSuggestion('Quel est votre niveau de compréhension ?')">Quel est votre niveau de compréhension ?</li>
        </ul>
    </div>
</div>

<!-- Modale pour afficher les réponses -->
<div id="responsesModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeResponsesModal()">&times;</span>
        <div id="responsesContent"></div>
    </div>
</div>

<script>
// Fonction pour afficher la modale d'ajout de question
function showAddQuestionModal() {
    var modal = document.getElementById('addQuestionModal');
    modal.classList.add('show');
}

// Fonction pour fermer la modale d'ajout de question
function closeAddQuestionModal() {
    var modal = document.getElementById('addQuestionModal');
    modal.classList.remove('show');
}

// Fonction pour copier le lien de la classe et afficher le pop-up
function shareClass() {
    var classId = "<?php echo $classId; ?>";
    var link = "https://lacavernedeplaton.fr/ticket/ticket.php?classe=" + classId;
    navigator.clipboard.writeText(link).then(function() {
        var popup = document.getElementById('popup');
        popup.classList.add('show');
        setTimeout(function() {
            popup.classList.remove('show');
        }, 2000);
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}

// Fonction pour afficher la modale des suggestions
function showSuggestionsModal() {
    var modal = document.getElementById('suggestionsModal');
    modal.classList.add('show');
}

// Fonction pour fermer la modale des suggestions
function closeSuggestionsModal() {
    var modal = document.getElementById('suggestionsModal');
    modal.classList.remove('show');
}

// Fonction pour sélectionner une suggestion et la mettre dans le textarea
function selectSuggestion(text) {
    var questionText = document.getElementById('questionText');
    questionText.value = text;
    closeSuggestionsModal();
}

// Fonction pour afficher la modale des réponses
function showResponsesModal(questionId) {
    var responsesContent = document.getElementById('responsesContent');
    responsesContent.innerHTML = ''; // Effacer le contenu précédent

    var responses = <?php echo json_encode($responses); ?>;
    var filteredResponses = responses["<?php echo $classId; ?>"].filter(function(response) {
        return response.answers.some(function(answer) {
            return answer.questionId == questionId;
        });
    });

    if (filteredResponses.length > 0) {
        filteredResponses.forEach(function(response) {
            response.answers.forEach(function(answer) {
                if (answer.questionId == questionId) {
                    var p = document.createElement('p');
                    p.innerText = response.name + ': ' + answer.answer;
                    responsesContent.appendChild(p);
                }
            });
        });
    } else {
        var p = document.createElement('p');
        p.innerText = 'Aucune réponse trouvée.';
        responsesContent.appendChild(p);
    }

    var modal = document.getElementById('responsesModal');
    modal.classList.add('show');
}

// Fonction pour fermer la modale des réponses
function closeResponsesModal() {
    var modal = document.getElementById('responsesModal');
    modal.classList.remove('show');
}

// Fermer les modales lorsqu'on clique à l'extérieur de celles-ci
window.onclick = function(event) {
    var addModal = document.getElementById('addQuestionModal');
    var suggestionsModal = document.getElementById('suggestionsModal');
    var responsesModal = document.getElementById('responsesModal');
    if (event.target == addModal) {
        addModal.classList.remove('show');
    }
    if (event.target == suggestionsModal) {
        suggestionsModal.classList.remove('show');
    }
    if (event.target == responsesModal) {
        responsesModal.classList.remove('show');
    }
}
</script>

</body>
</html>