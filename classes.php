<?php
// Lire les classes depuis le fichier JSON
function getClasses() {
    $json = file_get_contents('classes.json');
    return json_decode($json, true);
}

// Sauvegarder les classes dans le fichier JSON
function saveClasses($classes) {
    $json = json_encode($classes, JSON_PRETTY_PRINT);
    file_put_contents('classes.json', $json);
}

// Ajouter une nouvelle classe
if (isset($_POST['addClass'])) {
    $classes = getClasses();
    $newClass = [
        'id' => count($classes) + 1,
        'name' => $_POST['className'],
        'pin' => $_POST['classPin']
    ];
    $classes[] = $newClass;
    saveClasses($classes);
}

// Supprimer une classe
if (isset($_GET['deleteClass'])) {
    $classes = getClasses();
    $classes = array_filter($classes, function($class) {
        return $class['id'] != $_GET['deleteClass'];
    });
    saveClasses($classes);
}

$classes = getClasses();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Classes</title>
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
        .form-group input[type="text"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
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
        .class-list {
            margin-top: 20px;
        }
        .class-item {
            padding: 10px;
            background-color: #e9e9e9;
            border-radius: 4px;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .class-item button {
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            padding: 5px 10px;
        }
        .class-item button:hover {
            background-color: #e53935;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Gestion des Classes</h2>
        <form method="post">
            <div class="form-group">
                <label for="className">Nom de la classe</label>
                <input type="text" id="className" name="className" required>
            </div>
            <div class="form-group">
                <label for="classPin">Code PIN</label>
                <input type="text" id="classPin" name="classPin" required>
            </div>
            <div class="form-group">
                <button type="submit" name="addClass">Ajouter la classe</button>
            </div>
        </form>
        <div class="class-list">
            <?php foreach ($classes as $class): ?>
                <div class="class-item">
                    <?php echo htmlspecialchars($class['name']); ?>
                    <a href="classe.php?id=<?php echo $class['id']; ?>">Gérer</a>
                    <button onclick="window.location.href='?deleteClass=<?php echo $class['id']; ?>'">Supprimer</button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>