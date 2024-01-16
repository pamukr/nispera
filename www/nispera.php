<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include_once 'db_connect.php';
// Verificar si se ha enviado una solicitud POST
if (!isset($_SESSION)) {
    session_start();
}

//Variables generals
$domain = "http://localhost:8080/";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    function uploadHeader($id, $name, $surname, $username, $email)
    {
?>
        <style src="style.css"></style>
        <header>
            <div class="logo"><span>Education</span></div>
            <div class="actual_user">
                <p id="fullname"><?php echo $name . " " . $surname; ?></p>
                <img src=<?php $img = obtainImage($id);
                            echo "\"$img\""; ?> alt="user_portrait" id="portrait" onclick="editProfile()" />
            </div>
        </header>
        <div class="edit-profile height-0" id="edit-profile">
            <div class="nav">
                <i class='bx bx-undo' onclick="editProfile()"></i>
                <div id="nav-extra"><button onclick="logOut()">Log Out</button></div>
            </div>
            <div><input id="pp" type="file" id="portrait" onchange=<?php echo "\"updateImgPreview(this); changePP($id)\""; ?>><label for="portrait" style=<?php $img = obtainImage($id);
                                                                                                                                                            echo "background-image:url(\"$img\")"; ?>></label></div>
            <div>
                <p id="user-name"><?php echo $name; ?></p>
            </div>
            <div>
                <p id="user-surname"><?php echo $surname; ?></p>
            </div>
            <div><i class='bx bxs-envelope'></i>
                <p id="user-email"><?php echo $email; ?></p>
            </div>
            <div><i class='bx bxs-user'></i>
                <p id="user-username"><?php echo $username; ?></p>
            </div>
            <h3>Change Password</h3>
            <hr class="user">
            <div><i class='bx bxs-lock'></i><input type="password" id="user-current-password" placeholder="Introduce your current password"></div>
            <div><i class='bx bxs-key'></i><input type="password" id="user-new-password" placeholder="Introduce your new password"></div>
            <div class="save_button_container">
                <button onclick="changePassword()" class="user save_button"><i class='bx bxs-save'></i>Save</button>
            </div>
        </div>
        <?php
    }


    function obtainImage($id)
    {
        $path = getcwd() . "/" . "assets/pp/";
        //Buscamos en la carpeta el archivo que tenga el id del usuario.
        $files = glob($path . $id . ".*");
        if (!empty($files)) {
            // Devolvemos la imagen en formato base64
            $ext = pathinfo($files[0], PATHINFO_EXTENSION);
            $data = file_get_contents($files[0]);
            $base64 = 'data:image/' . $ext . ';base64,' . base64_encode($data);
            return $base64;
        } else {
            // Devolvemos la imagen por defecto en formato base64
            $data = file_get_contents($path . "default.png");
            $base64 = 'data:image/png;base64,' . base64_encode($data);
            return $base64;
        }
    }

    if (isset($_POST['import'])) {
        //Creamos una array de objetos llamado users
        $users = array();
        //Obtenemos el arvhivo csv
        $csv = $_FILES['csv'];
        //Verificamos que sea un csv
        $ext = pathinfo($csv['name'], PATHINFO_EXTENSION);
        //Convertimos el csv en una array de objetos a partir de los valores de la primera linea
        if ($ext === "csv") {
            // Obtén el archivo CSV
            $csv = $_FILES['csv'];

            // Verifica que sea un archivo CSV
            $ext = pathinfo($csv['name'], PATHINFO_EXTENSION);
            if ($ext === "csv") {
                // Lee el contenido del archivo CSV
                $lines = file($csv['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                // Obtén la primera línea y divide los valores
                $keys = explode(',', $lines[0]);

                // Crea un array vacío para almacenar los objetos
                $users = array();

                // Recorre las líneas restantes del archivo CSV
                for ($i = 1; $i < count($lines); $i++) {
                    // Divide la línea en valores individuales
                    $values = explode(',', $lines[$i]);

                    // Crea un objeto y asigna los valores correspondientes
                    $user = new stdClass();
                    for ($j = 0; $j < count($keys); $j++) {
                        $key = trim($keys[$j]);
                        $value = trim($values[$j]);
                        $user->$key = $value;
                    }

                    // Agrega el objeto al array de usuarios
                    $users[] = $user;
                }

                // Ahora tienes la matriz de objetos $users
                // Puedes acceder a los valores de cada objeto utilizando la sintaxis $users[index]->clave
            }
            //Recorremos la array de $users



        }
    }

    if (isset($_POST['changePP'])) {
        $pp = $_FILES['pp'];
        $id = $_POST['userid'];
        if ($_SESSION['role'] === "admin" || $id === $_SESSION['id']) {
            //Eliminamos la imagen $id.ext de la carpeta assets/pp/
            $path = getcwd() . "/" . "assets/pp/";
            //Buscamos en la carpeta el archivo que tenga el id del usuario.
            $files = glob($path . $id . ".*");
            if (!empty($files)) {
                // Eliminamos el archivo
                unlink($files[0]);
            }
            //Creamos una imagen en la carpeta assets/pp/ con $pp
            $ext = pathinfo($pp['name'], PATHINFO_EXTENSION);
            $filename = $id . "." . $ext;
            move_uploaded_file($pp['tmp_name'], $path . $filename);
        }
    }

    $data = json_decode(file_get_contents("php://input"), true);
    if (isset($data['action'])) {
        $action = $data['action'];
        switch ($action) {
            case 'logout':
                session_destroy();
                break;
            case 'login':
                $username = $data['username'];
                $password = $data['password'];

                //Obtén la contraseña almacenada de ese usuario
                $result = $conn->query("SELECT password_hash FROM users WHERE user = '$username'");


                //Si result es falso, entonces el usuario no existe
                if (!$result) {
                    echo 'Error: El usuario no existe.';
                } else {
                    //Obtén la contraseña almacenada
                    $row = $result->fetch_assoc();
                    $stored_hash = $row['password_hash'];

                    //Compara que stored_hash y password sean iguales
                    if ($password === hash('sha256', $stored_hash)) {
                        //Guarda en la sessión el nombre del usuario logueado
                        $_SESSION['username'] = $username;
                        $result = $conn->query("SELECT id, role FROM users WHERE user = '$_SESSION[username]'");
                        $row = $result->fetch_assoc();
                        $_SESSION['role'] = $row['role'];
                        $_SESSION['id'] = $row['id'];
                        echo 'true';
                    } else {
                        echo 'false';
                    }
                };
                break;
                //Principal
            case 'main':
                if (!isset($_SESSION['username'])) {
                    header("Location: index.html");
                    exit();
                } else if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
        ?>
                    <!DOCTYPE html>
                    <html lang="en">

                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Home</title>
                        <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
                        <link rel="stylesheet" href="assets/style/style.css" />
                    </head>

                    <body>
                        <section id="main">
                            <?php
                            //Cargamos el header
                            $result = $conn->query("SELECT name, surname, email, user FROM users WHERE id = '$_SESSION[id]'");
                            $row = $result->fetch_assoc();
                            $name = $row['name'];
                            $surname = $row['surname'];
                            $email = $row['email'];
                            $username = $row['user'];
                            uploadHeader($_SESSION['id'], $name, $surname, $username, $email); ?>
                            <h2>Active Projects</h2>
                            <hr class="project">

                            <div class="tag nproject" onclick="createProject()">
                                <h3>New Project</h3>
                                <i class='bx bx-plus'></i>
                            </div>

                            <?php
                            //Obtenemos el id del usuario $_SESSION['username']
                            $id = $_SESSION['id'];

                            //Obtenemos los proyectos del usuario
                            $result = $conn->query("SELECT project_id FROM project_users WHERE user_id = '$id' ORDER BY project_id DESC");
                            //Recorremos los proyectos
                            while ($row = $result->fetch_assoc()) {
                                $projectid = $row['project_id'];
                                //Obtenemos el nombre del proyecto
                                $project = $conn->query("SELECT name FROM projects WHERE id = '$projectid'");
                                $project = $project->fetch_assoc()['name'];
                                echo "<div class='scroll_tag project' onmousedown='handleStart(this)' ontouchstart='handleStart(this)' action='goProject' id='$projectid'>";
                                echo "<h3>$project</h3>";
                                echo '<i class="bx bx-chevrons-right"></i></div>';
                            }
                            ?>
                            </div>
                            <?php
                            if ($_SESSION['role'] === 'admin') {
                                echo '<input type="file" id="import" onchange="uploadCSV()">';
                                echo "<button onclick='action(\"goUsers\")'>Users</button>";
                            }
                            ?>
                        </section>
                        <script src="scroll.js"></script>
                    </body>
                    <?php include_once "scripts.php"; ?>
                    <script>
                        function createProject() {
                            fetchNispera({
                                name: "action",
                                value: "createProject"
                            }, {
                                name: "new",
                                value: "true"
                            }).then(response => {
                                document.open();
                                document.write(response);
                                document.close();
                            });
                        }

                        function goProject(id) {
                            fetchNispera({
                                name: "action",
                                value: "goProject"
                            }, {
                                name: "id",
                                value: id
                            }).then(response => {
                                document.open();
                                document.write(response);
                                document.close();
                            });
                        }


                        <?php
                        if ($_SESSION['role'] === 'admin') {
                        ?>

                            function uploadCSV() {
                                let csv = document.getElementById("import").files[0];
                                let data = new FormData();
                                data.append('csv', csv);
                                data.append('import', 'true');
                                fetch('nispera.php', {
                                        method: 'POST',
                                        body: data
                                    })
                                    .then(response => response.text())
                                    .then(content => {
                                        console.log(content);
                                    });
                            }
                        <?php
                        }
                        ?>
                    </script>

                    </html>
                <?php
                } else if ($_SESSION['role'] === 'student') {
                    //En el cas de ser un alumne.
                ?>
                    <!DOCTYPE html>
                    <html lang="en">

                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Home</title>
                    </head>

                    <body>
                        <h1 id="uwu">Bienvenido <?php echo $_SESSION['username'] ?></h1>
                        <button onclick="logOut()">Cerrar sessión</button>
                    </body>
                    <?php include_once "scripts.php"; ?>
                    <script>
                    </script>

                    </html>

                    <?php
                }
                break;

                //Projects
            case 'createProject':
                //Si create es true, entonces se ha creado un nuevo proyecto
                if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                    if (isset($data['create'])) {
                        if ($_SESSION['current_name'] != "") {
                            if (isset($data['skills'])) {
                                $_SESSION['current_skills'] = $data['skills'];
                            }
                            $name = $_SESSION['current_name'];
                            $people = substr($_SESSION['current_people'], 0, -1);
                            $skills = substr($_SESSION['current_skills'], 0, -1);

                            //Si no existe un proyecto con ese nombre, lo creamos
                            $result = $conn->query("SELECT id FROM projects WHERE name = '$name'");
                            if ($result->num_rows === 0) {
                                //Creamos el proyecto y obtenemos su id
                                $conn->query("INSERT INTO projects (name,status) VALUES ('$name','open')");
                                $projectid = $conn->insert_id;

                                // Obtenemos el id del usuario $_SESSION['username']
                                $teacherid = $_SESSION['id'];

                                // Añadimos el usuario al proyecto
                                $conn->query("INSERT INTO project_users (user_id,project_id) VALUES ('$teacherid','$projectid')");

                                if ($people != "") {
                                    //Separamos la lista de personas en un array
                                    $people = explode(";", $people);
                                    //Recorremos el array
                                    foreach ($people as $person) {
                                        // QUitale los # a la id
                                        $userid = str_replace("#", "", $person);

                                        // Verificar si tienen algún grupo en común
                                        $query = "SELECT COUNT(*) AS count FROM groupmembers WHERE user_id = '$teacherid' AND group_id IN (SELECT group_id FROM groupmembers WHERE user_id = '$userid')";
                                        $result = $conn->query($query);
                                        $row = $result->fetch_assoc();
                                        $count = $row['count'];

                                        if ($count > 0) {
                                            // Tienen grupos en común
                                            //Añadimos la persona al proyecto
                                            $conn->query("INSERT INTO project_users (user_id,project_id) VALUES ('$userid','$projectid')");
                                        }
                                    }
                                }
                                //Separamos la lista de skills en un array
                                if ($skills != "") {
                                    $skills = explode(";", $skills);
                                    //Recorremos el array
                                    //Primero verificamos que los range sumados den exactamente 100
                                    $percentage = "0";
                                    foreach ($skills as $skill) {
                                        $skill = explode(":", $skill);
                                        $percentage += $skill[1];
                                    }
                                    if ($percentage == 100) {
                                        foreach ($skills as $skill) {
                                            //Separamos el id de la skill y el rango
                                            $skill = explode(":", $skill);
                                            // Quita los # a la id
                                            $skillid = intval(str_replace("#", "", $skill[0]));
                                            $range = $skill[1];
                                            //Añadimos la skill al proyecto
                                            $conn->query("INSERT INTO project_skills (project_id,skill_id,percentage) VALUES ('$projectid','$skillid','$range')");
                                        }
                                        echo "true";
                                    } else {
                                        echo "Wrong percentage";
                                        //S'elimina el projecte amb id $projectid
                                        $conn->query("DELETE FROM project_users WHERE project_id = '$projectid'");
                                        $conn->query("DELETE FROM projects WHERE id = '$projectid'");
                                    }
                                } else {
                                    echo "You need to add skills";
                                    //S'elimina el projecte amb id $projectid
                                    $conn->query("DELETE FROM project_users WHERE project_id = '$projectid'");
                                    $conn->query("DELETE FROM projects WHERE id = '$projectid'");
                                }
                            } else {
                                echo "Wrong project name";
                            }
                        } else {
                            echo "Empty project name";
                        }
                    } else {
                        if (isset($data['new'])) {
                            $result = $conn->query("SELECT COUNT(id) FROM projects");
                            $row = $result->fetch_assoc();
                            $_SESSION['current_name'] = "";
                            $_SESSION['current_people'] = "";
                            $_SESSION['current_skills'] = "";
                        }
                    ?>
                        <!DOCTYPE html>
                        <html lang="en">

                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <title>Create Project</title>
                            <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
                            <link rel="stylesheet" href="assets/style/style.css" />
                        </head>

                        <body>
                            <?php
                            //Cargamos el header
                            $result = $conn->query("SELECT name, surname, email, user FROM users WHERE id = '$_SESSION[id]'");
                            $row = $result->fetch_assoc();
                            $name = $row['name'];
                            $surname = $row['surname'];
                            $email = $row['email'];
                            $username = $row['user'];
                            uploadHeader($_SESSION['id'], $name, $surname, $username, $email); ?>
                            <section id="main">
                                <div class="nav">
                                    <i class='bx bx-undo' onclick='action("main")'></i>
                                    <div id="nav-extra"></div>
                                </div>
                                <div class="title"><input type="text" id="name" placeholder="name" value=<?php echo '"' . $_SESSION['current_name'] . '"'; ?>></div>
                                <h2>Students</h2>
                                <hr class="team">
                                <div class="tag team" onclick="addPeople()">
                                    <h3>Add People</h3>
                                    <i class='bx bx-plus'></i>
                                </div>
                                <div class="skill-flex-2">
                                    <div>
                                        <h2>Skills</h2>
                                    </div>
                                    <div class="showTotalPercent">
                                        <p>= <span id="totalPercent"></span>%</p>
                                    </div>
                                </div>
                                <hr class="skill">

                                <div class="tag nskill" onclick="addSkill()">
                                    <h3>New Skill</h3>
                                    <i class='bx bx-plus'></i>
                                </div>
                                <?php
                                //Si no hay skills, no imprimimos nada
                                if ($_SESSION['current_skills'] != "") {
                                    //Se le quita el ultimo ; a las current_skills
                                    $skills = substr($_SESSION['current_skills'], 0, -1);
                                    //Separamos la lista de skills en un array
                                    $skills = explode(";", $skills);
                                    //Recorremos el array
                                    echo "<div id='skills'>";
                                    foreach ($skills as $skill) {
                                        //Separamos el id de la skill y el rango
                                        $skill = explode(":", $skill);
                                        // QUitale los # a la id
                                        $id = str_replace("#", "", $skill[0]);
                                        $range = $skill[1];

                                        //Obtenemos el nombre i la imagen de la skill
                                        $result = $conn->query("SELECT name, src FROM skills WHERE id = '$id'");
                                        $row = $result->fetch_assoc();
                                        $name = $row['name'];
                                        $src = $row['src'];
                                        $path = "assets/skills/black/";
                                        //Imprimimos la skill
                                ?>
                                        <div class="edit-percent">
                                            <input id=<?php echo "\"#$id#\""; ?> type="range" class="skill-percent" min="0" max="100" value=<?php echo "\"$range\""; ?>>
                                            <div class="edit-percent-inner">
                                                <div><img src=<?php echo "\"$domain$path$src\""; ?> alt=<?php echo "\"$name\""; ?>>
                                                    <p><?php echo "$name"; ?></p>
                                                </div>
                                                <div>
                                                    <p class="percentage-display">0%</p>
                                                </div>
                                            </div>
                                        </div>
                                <?php
                                    }
                                    echo "</div>";
                                }

                                ?>

                                <div class="save_button_container">
                                    <button class="save_button project" onclick="create()"><i class='bx bxs-save'></i>Save</button>
                                </div>
                            </section>
                            <script src="limit100.js"></script>
                        </body>
                        <?php include_once "scripts.php"; ?>
                        <script>
                            function updateName() {
                                let name = document.getElementById("name").value;
                                fetchNispera({
                                    name: "action",
                                    value: "updateProjectName"
                                }, {
                                    name: "name",
                                    value: name
                                });
                            }

                            function addPeople() {
                                updateName();
                                fetchNispera({
                                    name: "action",
                                    value: "addPeople"
                                }, {
                                    name: "create",
                                    value: "true"
                                }).then(response => {
                                    document.open();
                                    document.write(response);
                                    document.close();
                                });
                            }

                            function addSkill() {
                                updateName();
                                fetchNispera({
                                    name: "action",
                                    value: "addSkill"
                                }, {
                                    name: "create",
                                    value: "true"
                                }).then(response => {
                                    document.open();
                                    document.write(response);
                                    document.close();
                                });
                            }

                            function create() {
                                updateName();
                                let name = document.getElementById("name").value;
                                //Si hay inputs en el div de skills
                                if (document.getElementById("skills")) {
                                    let skills = document.getElementById("skills").children;
                                    var skillList = "";
                                    for (let i = 0; i < skills.length; i++) {
                                        let skill = skills[i];
                                        let id = skill.querySelector(".skill-percent").id;
                                        let range = skill.querySelector(".skill-percent").value;
                                        skillList += id + ":" + range + ";";
                                    }
                                }

                                fetchNispera({
                                    name: "action",
                                    value: "createProject"
                                }, {
                                    name: "create",
                                    value: "true"
                                }, {
                                    name: "skills",
                                    value: skillList
                                }).then(response => {
                                    //Si la respuesta es true, entonces se ha creado el proyecto
                                    if (response === "true") {
                                        action("main");
                                        showStatus(true, "Project created");
                                    } else {
                                        showStatus(false, response);
                                    }
                                });
                            }
                        </script>

                        </html>

                    <?php
                    }
                }
                break;
            case "updateProjectName":
                if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                    $name = $data['name'];
                    $_SESSION['current_name'] = $name;
                }
                break;
            case "goProject":
                if (isset($data['id'])) {
                    // Miramos si $_SESSION['id'] está en el proyecto
                    $result = $conn->query("SELECT COUNT(*) AS count FROM project_users WHERE user_id = '{$_SESSION['id']}' AND project_id = '{$data['id']}'");
                    $row = $result->fetch_assoc();
                    $count = $row['count'];
                    if ($count > 0) {
                        $_SESSION['current_project'] = $data['id'];
                        //Obtenemos la gente que hay en el proyecto
                    }
                }
                if (isset($_SESSION['current_project']) && $_SESSION['current_project'] != "") {
                    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                        $result = $conn->query("SELECT user_id FROM project_users WHERE project_id = '{$_SESSION['current_project']}'");
                        $people = "";
                        while ($row = $result->fetch_assoc()) {
                            $people .= "#" . $row['user_id'] . "#;";
                        }
                        $_SESSION['current_people'] = $people;
                        //Obtenemos las skills con el rango del proyecto
                        $result = $conn->query("SELECT skill_id, percentage FROM project_skills WHERE project_id = '{$_SESSION['current_project']}'");
                        $skills = "";
                        while ($row = $result->fetch_assoc()) {
                            $skills .= "#" . $row['skill_id'] . "#:" . $row['percentage'] . ";";
                        }
                        $_SESSION['current_skills'] = $skills;
                        $_SESSION['current_activity'] = "";
                        //Obtenemos el nombre del proyecto
                        $result = $conn->query("SELECT name FROM projects WHERE id = '{$_SESSION['current_project']}'");
                        $row = $result->fetch_assoc();
                        $projectname = $row['name'];
                    ?>
                        <!DOCTYPE html>
                        <html lang="en">

                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <title><?php echo  $projectname; ?></title>
                            <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
                            <link rel="stylesheet" href="assets/style/style.css" />
                        </head>

                        <body>
                            <?php
                            //Cargamos el header
                            $result = $conn->query("SELECT name, surname, email, user FROM users WHERE id = '$_SESSION[id]'");
                            $row = $result->fetch_assoc();
                            $name = $row['name'];
                            $surname = $row['surname'];
                            $email = $row['email'];
                            $username = $row['user'];
                            uploadHeader($_SESSION['id'], $name, $surname, $username, $email);
                            ?>
                            <section id="main">
                                <div class="nav">
                                    <i class='bx bx-undo' onclick="action('main')"></i>
                                    <div id="nav-extra" onclick="action('editProject')"><i class='bx bxs-edit-alt'></i>
                                        <p>Edit <span id="nav-extra-type">Project</span></p>
                                    </div>
                                </div>
                                <h1><?php echo  $projectname; ?></h1>
                                <div class="scroll_tag skill" onclick="handleClick(this)" onmousedown="handleStart(this)" ontouchstart="handleStart(this)" action="goSkills">
                                    <h3>Skills</h3>
                                    <i class='bx bx-chevrons-right'></i>
                                </div>
                                <div class="tag team" onclick="action('addPeople')">
                                    <h3>Add People</h3>
                                    <i class='bx bx-plus'></i>
                                </div>
                                <h2>Activities</h2>
                                <hr class="activity">

                                <div class="tag nactivity" onclick="action('editActivity')">
                                    <h3>New Activity</h3>
                                    <i class='bx bx-plus'></i>
                                </div>
                                <div id="activities">
                                    <?php
                                    //Obtenemos las actividades del proyecto
                                    $result = $conn->query("SELECT id, name, status FROM activities WHERE project_id = '{$_SESSION['current_project']}' ORDER BY id DESC");
                                    //Recorremos las actividades
                                    while ($row = $result->fetch_assoc()) {
                                        $id = $row['id'];
                                        $name = $row['name'];
                                        $status = $row['status'];
                                        echo "<div>";
                                        echo "<button class='$status' onclick='goActivity($id)'>$name</button>";
                                        echo "</div>";
                                    }
                                    ?>
                                </div>
                            </section>
                            <script src="scroll.js"></script>
                        </body>
                        <?php include_once "scripts.php"; ?>
                        <script>
                            var screenWidth =
                                window.innerWidth ||
                                document.documentElement.clientWidth ||
                                document.body.clientWidth;
                            var porcentajeEnPixeles = (30 / 100) * screenWidth;
                            var isDragging = false;
                            var draggedElement = null;
                            var firstx = false;
                            var scroll_tag = document.querySelectorAll(".scroll_tag");

                            function handleStart(element) {
                                if (element.classList.contains("scroll_tag")) {
                                    isDragging = true;
                                    draggedElement = element;
                                }
                            }

                            document.addEventListener("mouseup", handleEnd);
                            document.addEventListener("touchend", handleEnd);
                            document.addEventListener("mousemove", handleMove);
                            document.addEventListener("touchmove", handleMove);

                            function handleEnd() {
                                draggedElement.style.transition = "all 0.6s ease";
                                isDragging = false;
                                draggedElement = null;
                                firstx = false;
                                scroll_tag.forEach((element) => {
                                    element.style.transform = "translateX(-30%)";
                                });
                            }
                            var done = true;

                            function handleMove(event) {
                                if (isDragging && draggedElement) {
                                    let x;
                                    draggedElement.style.transition = "all 0s ease";

                                    if (event.type === "mousemove") {
                                        x = event.clientX;
                                    } else if (event.type === "touchmove") {
                                        // event.preventDefault(); // Prevent scrolling
                                        var touches = event.touches;
                                        if (touches.length > 0) {
                                            var touch = touches[0];
                                            x = touch.clientX || touch.pageX;
                                        }
                                    }

                                    if (x !== undefined) {
                                        if (!firstx) {
                                            firstx = x;
                                        }
                                        if (x > firstx) {
                                            var translateXValue = x - firstx;
                                            if (translateXValue > porcentajeEnPixeles - 30) {
                                                var action = draggedElement.getAttribute("action");
                                                setTimeout(() => {
                                                    if (done) {
                                                        done = false;
                                                        console.log(action);
                                                        if (action == "goskills") {
                                                            action("goSkills");
                                                        } else {
                                                            goActivity(action);
                                                        }
                                                    }
                                                }, 300);
                                            } else {
                                                draggedElement.style.transform =
                                                    "translateX(calc(" + translateXValue + "px - 30%))";
                                            }
                                        }
                                    }
                                }
                            }

                            function goActivity(id) {
                                fetchNispera({
                                    name: "action",
                                    value: "goActivity"
                                }, {
                                    name: "id",
                                    value: id
                                }).then(response => {
                                    document.open();
                                    document.write(response);
                                    document.close();
                                });
                            }

                            function deleteActivity(id) {
                                fetchNispera({
                                    name: "action",
                                    value: "deleteActivity"
                                }, {
                                    name: "id",
                                    value: id
                                }).then(response => {
                                    console.log(response);
                                    action("goProject");
                                });
                            }
                        </script>

                        </html>

                    <?php
                    } else if ($_SESSION['role'] === 'student') {
                    }
                }
                break;
            case "editProject":
                if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                    //Obtenemos el nombre del proyecto
                    $result = $conn->query("SELECT name FROM projects WHERE id = '{$_SESSION['current_project']}'");
                    $row = $result->fetch_assoc();
                    $name = $row['name'];
                    ?>
                    <!DOCTYPE html>
                    <html lang="en">

                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Edit <?php echo $name; ?></title>
                    </head>

                    <body>
                        <button onclick="action('goProject')">back</button>
                        <button onclick="action('deleteProject');action('main');">Delete this project</button>
                        <div id="Activities">
                            <?php
                            //Obtenemos las actividades del proyecto
                            $result = $conn->query("SELECT id, name, status FROM activities WHERE project_id = '{$_SESSION['current_project']}'");
                            //Recorremos las actividades
                            while ($row = $result->fetch_assoc()) {
                                $id = $row['id'];
                                $name = $row['name'];
                                $status = $row['status'];
                                echo "<div>";
                                echo "<button class='$status' onclick='goActivity($id)'>$name</button><button onclick='deleteActivity($id)'>Delete</button>";
                                echo "</div>";
                            }
                            ?>
                        </div>
                    </body>
                    <?php include_once "scripts.php"; ?>
                    <script>
                        function goActivity(id) {
                            fetchNispera({
                                name: "action",
                                value: "goActivity"
                            }, {
                                name: "id",
                                value: id
                            }).then(response => {
                                document.open();
                                document.write(response);
                                document.close();
                            });
                        }

                        function deleteActivity(id) {
                            fetchNispera({
                                name: "action",
                                value: "deleteActivity"
                            }, {
                                name: "id",
                                value: id
                            }).then(response => {
                                console.log(response);
                                action("editProject");
                            });
                        }
                    </script>

                    </html>
                <?php
                }
                break;
            case "deleteProject":
                if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                    //Se verifica que el usuario esté en el proyecto
                    $result = $conn->query("SELECT COUNT(*) AS count FROM project_users WHERE user_id = '{$_SESSION['id']}' AND project_id = '{$_SESSION['current_project']}'");
                    $row = $result->fetch_assoc();
                    $count = $row['count'];
                    if ($count > 0) {
                        //Si no hay gente en el proyecto, lo borramos
                        $result = $conn->query("SELECT COUNT(*) AS count FROM project_users WHERE project_id = '{$_SESSION['current_project']}'");
                        $row = $result->fetch_assoc();
                        $count = $row['count'];
                        if ($count == 1) {
                            //Borramos al propio usuario del proyecto
                            $conn->query("DELETE FROM project_users WHERE project_id = '{$_SESSION['current_project']}' AND user_id = '{$_SESSION['id']}'");
                            //Borramos project_skills
                            $conn->query("DELETE FROM project_skills WHERE project_id = '{$_SESSION['current_project']}'");
                            //Borramos las actividades que esten en el proyecto
                            $result = $conn->query("SELECT id FROM activities WHERE project_id = '{$_SESSION['current_project']}'");
                            while ($row = $result->fetch_assoc()) {
                                $id = $row['id'];
                                //Borramos act_skills
                                $conn->query("DELETE FROM act_skills WHERE activity_id = '$id'");
                            }
                            $conn->query("DELETE FROM activities WHERE project_id = '{$_SESSION['current_project']}'");
                            $conn->query("DELETE FROM projects WHERE id = '{$_SESSION['current_project']}'");
                        }
                    }
                }
                break;
                //People
            case "goUsers":
                if ($_SESSION['role'] === 'admin') {
                    $_SESSION['current_user'] = "";
                ?>
                    <!DOCTYPE html>
                    <html lang="en">

                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Users</title>
                    </head>

                    <body>
                        <button onclick="action('main')">back</button>
                        <button onclick="action('editUser')">New User</button>
                        <h1>Administrators</h1>
                        <?php
                        $result = $conn->query("SELECT id, name, surname FROM users WHERE role = 'admin'");
                        while ($row = mysqli_fetch_assoc($result)) {
                            $id = $row['id'];
                            $name = $row['name'];
                            $surname = $row['surname'];
                            //Si el usuario no es el mismo que el que está logueado
                            if ($id != $_SESSION['id']) {
                                echo "<button onclick='editUser($id)'>$name $surname</button>";
                            } else {
                                echo "You";
                            }
                        }
                        ?>
                        <h1>Teachers</h1>
                        <?php
                        $result = $conn->query("SELECT id, name, surname FROM users WHERE role = 'teacher'");
                        while ($row = mysqli_fetch_assoc($result)) {
                            $id = $row['id'];
                            $name = $row['name'];
                            $surname = $row['surname'];
                            //Si el usuario no es el mismo que el que está logueado
                            echo "<button onclick='editUser($id)'>$name $surname</button>";
                        }
                        ?>
                        <h1>Students</h1>
                        <?php
                        $result = $conn->query("SELECT id, name, surname FROM users WHERE role = 'student'");
                        while ($row = mysqli_fetch_assoc($result)) {
                            $id = $row['id'];
                            $name = $row['name'];
                            $surname = $row['surname'];
                            //Si el usuario no es el mismo que el que está logueado
                            echo "<button onclick='editUser($id)'>$name $surname</button>";
                        }
                        ?>
                    </body>
                    <?php include_once "scripts.php"; ?>
                    <script>
                        function editUser(id) {
                            fetchNispera({
                                name: "action",
                                value: "editUser"
                            }, {
                                name: "id",
                                value: id
                            }).then(response => {
                                document.open();
                                document.write(response);
                                document.close();
                            });
                        }
                    </script>

                    </html>
                <?php
                }
                break;
            case "editUser":
                if ($_SESSION['role'] === 'admin') {
                    if (isset($data['id'])) {
                        $id = $data['id'];
                        $_SESSION['current_user'] = $id;
                        $result = $conn->query("SELECT name, surname, user, role, email FROM users WHERE id = '$id'");
                        $row = $result->fetch_assoc();
                        $name = $row['name'];
                        $surname = $row['surname'];
                        $user = $row['user'];
                        $role = $row['role'];
                        $email = $row['email'];
                    } else {
                        //Obtenemos cuantos usuarios hay y le sumamos uno
                        $result = $conn->query("SELECT COUNT(id) FROM users");
                        $row = $result->fetch_assoc();
                        $id = $row['COUNT(id)'] + 1;
                        $name = "";
                        $surname = "";
                        $user = "";
                        $role = "";
                        $email = "";
                    }

                ?>
                    <!DOCTYPE html>
                    <html lang="en">

                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Edit <?php echo $username; ?></title>
                    </head>

                    <body>
                        <button onclick="action('goUsers')">back</button>
                        <img src=<?php echo '"' . obtainImage($id) . '"'; ?> alt="profile picture" width="50px" height="50px">
                        <input onchange=<?php echo "\"changePP($id)\""; ?> type="file" id="pp">
                        <input type="text" id="name" placeholder="name" value=<?php echo '"' . $name . '"'; ?>>
                        <input type="text" id="surname" placeholder="surname" value=<?php echo '"' . $surname . '"'; ?>>
                        <br>
                        <input type="mail" id="email" placeholder="email" value=<?php echo '"' . $email . '"'; ?>>
                        <input type="text" id="user" placeholder="user" value=<?php echo '"' . $user . '"'; ?>>
                        <select id="role">
                            <option value="student" <?php if ($role === 'student') echo 'selected'; ?>>Student</option>
                            <option value="teacher" <?php if ($role === 'teacher') echo 'selected'; ?>>Teacher</option>
                        </select>
                        <?php
                        //Obtenemos todos los grupos de los que el usuario es groupmember
                        $result = $conn->query("SELECT group_id FROM groupmembers WHERE user_id = '$id'");
                        $groups = "";
                        while ($row = $result->fetch_assoc()) {
                            //Obtenemos el nombre del grupo
                            $result2 = $conn->query("SELECT name FROM `groups` WHERE id = '{$row['group_id']}'");
                            $row2 = $result2->fetch_assoc();
                            $groups .= $row2['name'] . ",";
                        }
                        //Si el 
                        //Quitamos la ultima coma
                        $groups = substr($groups, 0, -1);
                        //Ponemos el input groups
                        echo "<input type='text' id='groups' placeholder='Ungrouped' value='$groups'>";
                        ?>
                        <button onclick="saveUser()">Save User</button>
                    </body>
                    <?php include_once "scripts.php"; ?>
                    <script>
                        function saveUser() {
                            let name = document.getElementById("name").value;
                            let surname = document.getElementById("surname").value;
                            let user = document.getElementById("user").value;
                            let role = document.getElementById("role").value;
                            let email = document.getElementById("email").value;
                            let groups = document.getElementById("groups").value;
                            fetchNispera({
                                name: "action",
                                value: "saveUser"
                            }, {
                                name: "name",
                                value: name
                            }, {
                                name: "surname",
                                value: surname
                            }, {
                                name: "user",
                                value: user
                            }, {
                                name: "role",
                                value: role
                            }, {
                                name: "email",
                                value: email
                            }, {
                                name: "groups",
                                value: groups
                            }).then(response => {
                                console.log(response);
                                if (response == "true") {
                                    action("goUsers");
                                }
                            });
                        }
                    </script>

                    </html>
                <?php
                }
                break;
            case "saveUser":
                if ($_SESSION['role'] === 'admin') {
                    $name = $data['name'];
                    $surname = $data['surname'];
                    $user = $data['user'];
                    $role = $data['role'];
                    $email = $data['email'];
                    $groups = $data['groups'];

                    //Revisamos que ningun campo esté vacio a parte del de groups
                    if ($name === "" || $surname === "" || $user === "" || $role === "" || $email === "") {
                        echo "Empty fields";
                    } else {
                        //Revisamos que el usuario y el correo no existan ya, dejando a parte el propio usuario.
                        $result = $conn->query("SELECT COUNT(*) AS count FROM users WHERE (user = '$user' OR email = '$email' OR (name = '$name' AND surname = '$surname')) AND id != '{$_SESSION['current_user']}'");
                        $row = $result->fetch_assoc();
                        $count = $row['count'];
                        if ($count == 0) {
                            echo "true";
                            if ($_SESSION['current_user'] == "") {
                                $conn->query("INSERT INTO users (name,surname,user,role,email,password_hash) VALUES ('$name','$surname','$user','$role','$email','e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855')");
                                $id = $conn->insert_id;

                                //Por cada grupo que haya en $groups, añadimos al usuario a ese grupo
                                $groups = explode(",", $groups);
                                foreach ($groups as $group) {
                                    //Miramos si existe un grupo con ese nombre
                                    $result = $conn->query("SELECT id FROM `groups` WHERE name = '$group'");
                                    if ($result->num_rows === 0) {
                                        //Si no existe, lo creamos
                                        $conn->query("INSERT INTO `groups` (name) VALUES ('$group')");
                                        $groupid = $conn->insert_id;
                                    } else {
                                        //Si existe, obtenemos su id
                                        $row = $result->fetch_assoc();
                                        $groupid = $row['id'];
                                    }
                                    //Añadimos al usuario en el grupo
                                    $conn->query("INSERT INTO groupmembers (user_id,group_id) VALUES ('$id','$groupid')");
                                }
                            } else {
                                //Actualizamos el usuario
                                $conn->query("UPDATE users SET name = '$name', surname = '$surname', user = '$user', role = '$role', email = '$email' WHERE id = '{$_SESSION['current_user']}'");

                                //Eliminamos todos los grupos del usuario
                                $conn->query("DELETE FROM groupmembers WHERE user_id = '{$_SESSION['current_user']}'");
                                //Por cada grupo que haya en $groups, añadimos al usuario a ese grupo
                                $groups = explode(",", $groups);
                                foreach ($groups as $group) {
                                    //Miramos si existe un grupo con ese nombre
                                    $result = $conn->query("SELECT id FROM `groups` WHERE name = '$group'");
                                    if ($result->num_rows === 0) {
                                        //Si no existe, lo creamos
                                        $conn->query("INSERT INTO `groups` (name) VALUES ('$group')");
                                        $groupid = $conn->insert_id;
                                    } else {
                                        //Si existe, obtenemos su id
                                        $row = $result->fetch_assoc();
                                        $groupid = $row['id'];
                                    }
                                    //Añadimos al usuario en el grupo
                                    $conn->query("INSERT INTO groupmembers (user_id,group_id) VALUES ('{$_SESSION['current_user']}','$groupid')");
                                }
                            }
                        }
                    }
                }
                break;
            case "changePassword":
                //Obtenemos la contraseña nueva y antigua
                $current = hash('sha256', $data['current']);
                $new = hash('sha256', $data['new']);
                //Obtenemos la contraseña del usuario
                $result = $conn->query("SELECT password_hash FROM users WHERE id = '{$_SESSION['id']}'");
                $row = $result->fetch_assoc();
                $password = $row['password_hash'];
                //Si la contraseña antigua es igual a la del usuario
                if ($current === $password) {
                    //Actualizamos la contraseña
                    $conn->query("UPDATE users SET password_hash = '$new' WHERE id = '{$_SESSION['id']}'");
                    echo "true";
                } else {
                    echo "false";
                }
                break;
            case "addPeople":
                if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                ?>
                    <!DOCTYPE html>
                    <html lang="en">

                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Add People</title>
                        <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
                        <link rel="stylesheet" href="assets/style/style.css" />
                    </head>

                    <body>
                        <?php
                        //Cargamos el header
                        $result = $conn->query("SELECT name, surname, email, user FROM users WHERE id = '$_SESSION[id]'");
                        $row = $result->fetch_assoc();
                        $name = $row['name'];
                        $surname = $row['surname'];
                        $email = $row['email'];
                        $username = $row['user'];
                        uploadHeader($_SESSION['id'], $name, $surname, $username, $email);
                        ?>
                        <section id="main">
                            <div class="nav">
                                <i class='bx bx-undo' onclick='savePeople()'></i>
                                <div id="nav-extra"></div>
                            </div>
                            <div class="title">
                                <h1>Users</h1>
                            </div>

                            <div class="with-filter">
                                <h2>Teachers</h2>
                                <i class='bx bxs-filter-alt bx-flip-horizontal' onclick="filterSort('teachers', 'add-people-table')"></i>
                            </div>
                            <hr class="user">
                            <div id="teachers">
                                <?php
                                //Obtenemos el id del usuario $_SESSION['username']
                                $teacherid = $_SESSION['id'];

                                $result = $conn->query("SELECT id, name, surname FROM users WHERE role = 'teacher'");
                                // Loop through the result and create a checkbox for each user
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $id = $row['id'];
                                    // Verificar si tienen algún grupo en común
                                    $query = "SELECT COUNT(*) AS count FROM groupmembers WHERE user_id = '$teacherid' AND group_id IN (SELECT group_id FROM groupmembers WHERE user_id = '$id')";
                                    $result2 = $conn->query($query); // Use a different variable name for the inner query result
                                    $row2 = $result2->fetch_assoc();
                                    $count = $row2['count'];
                                    if ($count > 0 && $id != $teacherid) {
                                        $name = $row['name'];
                                        $surname = $row['surname'];
                                ?>
                                        <div class="add-people-table user-li">
                                            <div>
                                                <img src=<?php $img = obtainImage($id);
                                                            echo "\"$img\""; ?> alt="profile picture">
                                                <p><?php echo "$name $surname"; ?></p>
                                            </div>
                                            <div>
                                                <?php
                                                if (strpos($_SESSION['current_people'], "#" . $id . "#") === false) {
                                                    echo "<input type='checkbox' name='users' id='$id'>";
                                                } else {
                                                    echo "<input type='checkbox' name='users' id='#$id#' checked>";
                                                }
                                                ?>
                                            </div>
                                        </div>
                                <?php


                                    }
                                }
                                ?>
                            </div>
                            <div class="with-filter">
                                <h2>Active Students</h2>
                                <i class='bx bxs-filter-alt bx-flip-horizontal' onclick="filterSort('students', 'add-people-table')"></i>
                            </div>
                            <hr class="user">
                            <div class="group-tags-container">
                                <div>
                                    <div id="group-tags-suggestions">

                                    </div>
                                    <input type="text" id="searchGroup" placeholder="Search for a group..." oninput="updateSuggestions(this.value)">
                                    <i class='bx bxs-trash' onclick="clearInput(document.getElementById('searchGroup'))"></i>
                                </div>
                                <div id="group-tags">

                                </div>
                            </div>
                            <div id="students">
                                <?php
                                // Query to fetch all users with role 'student'
                                $result = $conn->query("SELECT id, name, surname FROM users WHERE role = 'student'");
                                // Loop through the result and create a checkbox for each user
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $id = $row['id'];
                                    // Verificar si tienen algún grupo en común
                                    $query = "SELECT COUNT(*) AS count FROM groupmembers WHERE user_id = '$teacherid' AND group_id IN (SELECT group_id FROM groupmembers WHERE user_id = '$id')";
                                    $result2 = $conn->query($query); // Use a different variable name for the inner query result
                                    $row2 = $result2->fetch_assoc();
                                    $count = $row2['count'];
                                    if ($count > 0) {
                                        $name = $row['name'];
                                        $surname = $row['surname'];
                                ?>
                                        <div class="add-people-table user-li">
                                            <div>
                                                <img src=<?php $img = obtainImage($id);
                                                            echo "\"$img\""; ?> alt="profile picture">
                                                <p><?php echo "$name $surname"; ?></p>
                                            </div>
                                            <div>
                                                <?php
                                                if (strpos($_SESSION['current_people'], "#" . $id . "#") === false) {
                                                    echo "<input type='checkbox' name='users' id='#$id#'>";
                                                } else {
                                                    echo "<input type='checkbox' name='users' id='#$id#' checked>";
                                                }
                                                ?>
                                            </div>
                                        </div>
                                <?php
                                    }
                                }
                                ?>
                            </div>
                        </section>
                    </body>
                    <?php include_once "scripts.php"; ?>
                    <script>
                        function savePeople() {
                            //Obtenemos todos los inputs que esten checked
                            let people = "";
                            let inputs = document.querySelectorAll("input:checked");
                            for (let i = 0; i < inputs.length; i++) {
                                people += inputs[i].id + ";";
                            }
                            console.log(people);
                            <?php if (isset($data['create'])) { ?>
                                fetchNispera({
                                    name: "action",
                                    value: "savePeople"
                                }, {
                                    name: "users",
                                    value: people
                                });
                                action("createProject");
                            <?php } else { ?>
                                fetchNispera({
                                    name: "action",
                                    value: "savePeople"
                                }, {
                                    name: "users",
                                    value: people
                                }, {
                                    name: "update",
                                    value: "true"
                                }).then(response => {
                                    console.log(response);
                                });
                                action("goProject");
                            <?php } ?>
                        }

                        function filterSort(containerId, itemClass) {
                            let container = document.getElementById(containerId);
                            let items = Array.from(container.getElementsByClassName(itemClass));

                            if (!filterSort.counter) {
                                filterSort.counter = 1;
                            } else {
                                filterSort.counter *= -1;
                            }

                            let sortedItems = items.slice().sort((a, b) => {
                                let nameA = a.querySelector('p').textContent.toLowerCase();
                                let nameB = b.querySelector('p').textContent.toLowerCase();

                                return filterSort.counter * nameA.localeCompare(nameB);
                            });

                            items.forEach(person => container.removeChild(person));
                            sortedItems.forEach(person => container.appendChild(person));
                        }

                        function clearInput(input) {
                            input.value = '';
                            updateSuggestions('');
                        }

                        var suggestions = ['1r DAW', '2n DAW', '1r ASIX', '2n ASIX', 'DAM', 'DAM 2.0', 'ADE 2', 'ADE', '3r ASIX', '3r DAW'];
                        var choosenSuggestions = "";


                        function updateSuggestions(inputText) {
                            let suggestionsContainer = document.getElementById('group-tags-suggestions');
                            if (inputText == '') {
                                suggestionsContainer.innerHTML = '';
                            } else {
                                inputText = inputText.trim();
                                suggestions.sort();
                                suggestionsContainer.innerHTML = '';
                                suggestions.forEach(function(suggestion) {
                                    if (suggestion.toLowerCase().includes(inputText.toLowerCase())) {
                                        let suggestionElement = document.createElement('p');
                                        suggestionElement.className = 'suggestion';

                                        suggestionElement.textContent = suggestion;
                                        suggestionElement.onclick = function() {
                                            addTag(suggestion);
                                            clearInput(document.querySelector('input[type="text"]'));
                                        };
                                        suggestionsContainer.appendChild(suggestionElement);
                                    }
                                });
                            }
                        }


                        function addTag(tag) {
                            choosenSuggestions += tag + ';;';
                            let tagContainer = document.getElementById('group-tags');
                            let newTag = document.createElement('div');
                            newTag.className = 'team';
                            newTag.onclick = function() {
                                removeTag(this);
                            };
                            newTag.innerHTML = '<p>' + tag + '</p><i class="bx bx-x"></i>';
                            tagContainer.appendChild(newTag);
                            suggestions.splice(suggestions.indexOf(tag), 1);
                            updateSuggestions('');
                        }


                        function removeTag(tag) {
                            let tagContainer = document.getElementById('group-tags');
                            let removedTag = tag.querySelector('p').textContent;
                            suggestions.push(removedTag);
                            choosenSuggestions = choosenSuggestions.split(";;").filter(function(item) {
                                return item.trim() !== removedTag.trim();
                            }).join(";;");
                            tagContainer.removeChild(tag);
                            updateSuggestions('');
                        }
                    </script>

                    </html>
                <?php
                }
                break;

            case "savePeople":
                if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                    $people = $data['users'];
                    $_SESSION['current_people'] = $people;
                    if (isset($data['update'])) {
                        //Quitamos a toda la gente que está en el proyecto menos al mismo usuario
                        $conn->query("DELETE FROM project_users WHERE project_id = '{$_SESSION['current_project']}' AND user_id != '{$_SESSION['id']}'");

                        //Separamos $_SESSION['current_people'] en un array
                        if ($_SESSION['current_people'] != "") {
                            $people = explode(";", substr($_SESSION['current_people'], 0, -1));
                            //Recorremos el array
                            foreach ($people as $person) {
                                // Quitale los # a la id
                                $userid = str_replace("#", "", $person);
                                //Añadimos la persona al proyecto
                                $conn->query("INSERT INTO project_users (user_id,project_id) VALUES ('$userid','{$_SESSION['current_project']}')");
                            }
                        }
                    }
                }
                break;

                //Skills
            case "addSkill":
                ?>
                <!DOCTYPE html>
                <html lang="en">

                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Add Skill</title>
                    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
                    <link rel="stylesheet" href="assets/style/style.css" />
                </head>

                <body>
                    <?php
                    //Cargamos el header
                    $result = $conn->query("SELECT name, surname, email, user FROM users WHERE id = '$_SESSION[id]'");
                    $row = $result->fetch_assoc();
                    $name = $row['name'];
                    $surname = $row['surname'];
                    $email = $row['email'];
                    $username = $row['user'];
                    uploadHeader($_SESSION['id'], $name, $surname, $username, $email); ?>
                    <section id="main">
                        <?php if (isset($data['create'])) { ?>
                            <div class="nav">
                                <i class='bx bx-undo' onclick='action("createProject")'></i>
                                <div id="nav-extra"></div>
                            </div>
                        <?php } else { ?>
                            <div class="nav">
                                <i class='bx bx-undo' onclick='action("goSkills")'></i>
                                <div id="nav-extra"></div>
                            </div>
                        <?php }
                        $imagePath = "assets/skills/color";
                        ?>
                        <div class="skill-icon">
                            <div class="icon-background" onclick="changeIcon('show')"><img src=<?php echo "\"$imagePath/Adaptability.svg\"" ?> alt="skill-choose-icon"></div>
                            <input type="text" placeholder="Skill Name" id="skill-name">
                        </div>
                        <div class="choose-icon" id="choose-icon">
                            <div>
                                <p>Choose an icon</p><i class='bx bx-x' id="closeIconPanel" onclick="changeIcon('hide')"></i>
                            </div>
                            <hr class="generic">
                            <div class="icons">
                                <?php
                                $files = scandir($imagePath);
                                foreach ($files as $file) {
                                    $imageUrl = $domain . $imagePath . "/" . $file;
                                    $filename = explode(".", $file)[0];
                                    if ($filename) {
                                        echo "<label onclick='changeIcon(\"$imageUrl\")'><input type='radio' name='skill-image' value='$file'><img src='$imageUrl' alt='$filename'></label>";
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <div class="edit-range">
                            <div class="range-display">
                                <p><span id="skill-percentage-display">50</span>%</p>
                            </div>
                            <input type="range" id="skill-range" min="0" max="100" value="50">
                        </div>
                        <div class="save_button_container" onclick="saveSkill()">
                            <button class="skill save_button"><i class='bx bxs-save'></i>Save</button>
                        </div>
                    </section>
                </body>
                <?php include_once "scripts.php"; ?>
                <script>
                    function saveSkill() {
                        let name = document.getElementById("skill-name").value;
                        let range = document.getElementById("skill-range").value;
                        let image = document.querySelector('input[name="skill-image"]:checked').value;
                        fetchNispera({
                            name: "action",
                            value: "saveSkill"
                        }, {
                            name: "name",
                            value: name
                        }, {
                            name: "range",
                            value: range
                        }, {
                            name: "image",
                            value: image
                        });
                        <?php if (isset($data['create'])) { ?>
                            action("createProject");
                        <?php } else { ?>
                            action("goSkills");
                        <?php } ?>
                    }

                    var skillRange = document.getElementById('skill-range');
                    var skillpercentageDisplay = document.getElementById('skill-percentage-display');

                    skillRange.addEventListener('input', function() {
                        var currentValue = this.value;
                        skillpercentageDisplay.textContent = currentValue;
                    });

                    function changeIcon(action) {
                        var div = document.getElementById('choose-icon');
                        if (action == "show") {
                            div.style.height = "80dvh";
                        } else {
                            div.style.height = "0dvh";
                            if (action != "hide") {
                                document.querySelector('.icon-background img').src = action;
                            }
                        }
                    }
                </script>

                </html>
            <?php
                break;
            case "goSkills":
                echo $_SESSION['current_skills'];
            ?>
                <!DOCTYPE html>
                <html lang="en">

                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Skills</title>
                </head>

                <body>
                    <button onclick='updateSkills()'>back</button>
                    <button onclick='action("addSkill")'>New Skill</button>
                    <?php
                    //Si no hay skills, no imprimimos nada
                    if ($_SESSION['current_skills'] != "") {
                        //Se le quita el ultimo ; a las current_skills
                        $skills = substr($_SESSION['current_skills'], 0, -1);
                        //Separamos la lista de skills en un array
                        $skills = explode(";", $skills);
                        //Recorremos el array
                        echo "<div id='skills'>";
                        foreach ($skills as $skill) {
                            //Separamos el id de la skill y el rango
                            $skill = explode(":", $skill);
                            // QUitale los # a la id
                            $id = str_replace("#", "", $skill[0]);
                            $range = $skill[1];

                            //Obtenemos el nombre i la imagen de la skill
                            $result = $conn->query("SELECT name, src FROM skills WHERE id = '$id'");
                            $row = $result->fetch_assoc();
                            $name = $row['name'];
                            $src = $row['src'];
                            $path = "assets/skills/black/";
                            //Imprimimos la skill
                            echo "<div>";
                            echo "<img src='$domain$path$src' alt='$name'>";
                            echo "<p>$name</p>";
                            echo "<input id='#$id#' type='range' min='0' max='100' value='$range'>";
                            echo "</div>";
                        }
                        echo "</div>";
                    }

                    ?>
                </body>
                <?php include_once "scripts.php"; ?>
                <script>
                    function updateSkills() {
                        let skills = document.getElementById("skills").children;
                        var skillList = "";
                        for (let i = 0; i < skills.length; i++) {
                            let skill = skills[i];
                            let id = skill.children[2].id;
                            let range = skill.children[2].value;
                            skillList += id + ":" + range + ";";
                        }
                        fetchNispera({
                            name: "action",
                            value: "updateSkills"
                        }, {
                            name: "skills",
                            value: skillList
                        }).then(response => {
                            console.log(response);
                        });;
                        action("goProject");
                    }
                </script>

                </html>
                <?php
                break;
            case "saveSkill":
                if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                    $name = $data['name'];
                    $range = $data['range'];
                    $image = $data['image'];
                    //Verificamos si existe una skill con ese mismo nombre, si ya existe obtenemos el id, si no existe la creamos i obtenemos el id
                    $result = $conn->query("SELECT id FROM skills WHERE name = '$name'");
                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        //Cambiamos la imagen de la skill
                        $conn->query("UPDATE skills SET src = '$image' WHERE id = '$row[id]'");
                        $id = "#" . $row['id'] . "#";
                    } else {
                        $conn->query("INSERT INTO skills (name,src) VALUES ('$name','$image')");
                        $id = "#" . $conn->insert_id . "#";
                    }
                    //Si el id de la skill no está en la lista de skills, la añadimos
                    if (strpos($_SESSION['current_skills'], $id) === false) {
                        $_SESSION['current_skills'] .= "$id:$range;";
                    }

                    //Separamos $_SESSION['current_skills'] en un array
                }
                break;
            case "updateSkills":
                if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                    //Nos aseguramos que el proyecto actual no tenga actividades.
                    $result = $conn->query("SELECT id FROM activities WHERE project_id = '{$_SESSION['current_project']}'");
                    if ($result->num_rows == 0) {
                        if ($data['skills'] != "") {
                            $data['skills'] = preg_replace("/#[0-9]*#:0;/", "", $data['skills']);
                            $skills = explode(";", substr($data['skills'], 0, -1));
                            //Recorremos el array
                            //Primero verificamos que los range sumados den exactamente 100
                            $percentage = "0";
                            foreach ($skills as $skill) {
                                $skill = explode(":", $skill);
                                $percentage += $skill[1];
                            }

                            if ($percentage == 100) {
                                $_SESSION['current_skills'] = $data['skills'];
                                //Quitamos todas las skills del proyecto
                                $conn->query("DELETE FROM project_skills WHERE project_id = '{$_SESSION['current_project']}'");

                                foreach ($skills as $skill) {
                                    //Separamos el id de la skill y el rango
                                    $skill = explode(":", $skill);
                                    // Quita los # a la id
                                    $skillid = intval(str_replace("#", "", $skill[0]));
                                    $range = $skill[1];
                                    //Añadimos la skill al proyecto
                                    $conn->query("INSERT INTO project_skills (project_id,skill_id,percentage) VALUES ('{$_SESSION['current_project']}','$skillid','$range')");
                                }
                            } else {
                                echo "incorrect_skills";
                            }
                        }
                    }
                }
                break;
                //Activities
            case "editActivity":
                if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                    //Miramos si hay actividades en el proyecto y si las hay las recorremos
                    $possible_act_skills = [];

                    //Recorremos las skills del proyecto
                    $result = $conn->query("SELECT skill_id FROM project_skills WHERE project_id = '{$_SESSION['current_project']}'");
                    //Recorremos las skills
                    while ($row = $result->fetch_assoc()) {
                        $id = $row['skill_id'];
                        //Añadimos la skill junto al valor 100 como un objeto al array
                        array_push($possible_act_skills, (object) array('id' => $id, 'possible' => 100));
                    }

                    $result = $conn->query("SELECT id, name, description, status FROM activities WHERE project_id = '{$_SESSION['current_project']}'");
                    if ($result->num_rows > 0) {
                        //Recorremos las actividades
                        while ($row = $result->fetch_assoc()) {
                            $id = $row['id'];
                            //Obtenemos las skills de la actividad
                            $result2 = $conn->query("SELECT skill_id, percentage FROM act_skills WHERE activity_id = '$id'");
                            //Recorremos las skills
                            while ($row2 = $result2->fetch_assoc()) {
                                $id = $row2['skill_id'];
                                $percentage = $row2['percentage'];
                                //Encontramos el id en el array y le restamos el porcentaje a possible
                                foreach ($possible_act_skills as $key => $value) {
                                    if ($value->id == $id) {
                                        $possible_act_skills[$key]->possible -= $percentage;
                                    }
                                }
                            }
                        }
                    }
                    //Recorremos el array y eliminamos las skills que tengan possible 0
                    foreach ($possible_act_skills as $key => $value) {
                        if ($value->possible == 0) {
                            unset($possible_act_skills[$key]);
                        }
                    }
                    $_SESSION['possible_act_skills'] = $possible_act_skills;
                    echo json_encode($_SESSION['possible_act_skills']);
                    if ($_SESSION['current_activity'] == "") {
                ?>
                        <!DOCTYPE html>
                        <html lang="en">

                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <title>New Activity</title>
                        </head>

                        <body>
                            <button onclick='action("goProject")'>back</button>
                            <input id="name" type="text" placeholder="name">
                            <textarea id="description" placeholder="description"></textarea>
                            <div id="skills">
                                <?php
                                //Recorremos el array de skills
                                foreach ($possible_act_skills as $key => $value) {
                                    $id = $value->id;
                                    //Obtenemos el nombre i la imagen de la skill
                                    $result = $conn->query("SELECT name, src FROM skills WHERE id = '$id'");
                                    $row = $result->fetch_assoc();
                                    $name = $row['name'];
                                    $src = $row['src'];
                                    $path = "assets/skills/black/";
                                    //Imprimimos la skill
                                    echo "<div>";
                                    echo "<img src='$domain$path$src' alt='$name'>";
                                    echo "<p>$name</p>";
                                    echo "<input id='#$id#' type='range' min='0' max='100' value='0' block='$value->possible'>";
                                    echo "</div>";
                                }

                                ?>
                            </div>
                            <button onclick="saveActivity()">Save Activity</button>
                        </body>
                        <?php include_once "scripts.php"; ?>
                        <script>
                            function saveActivity() {
                                let name = document.getElementById("name").value;
                                let description = document.getElementById("description").value;
                                let skills = document.getElementById("skills").children;
                                var skillList = "";
                                for (let i = 0; i < skills.length; i++) {
                                    let skill = skills[i];
                                    let id = skill.children[2].id;
                                    let range = skill.children[2].value;
                                    skillList += id + ":" + range + ";";
                                }
                                fetchNispera({
                                    name: "action",
                                    value: "saveActivity"
                                }, {
                                    name: "name",
                                    value: name
                                }, {
                                    name: "description",
                                    value: description
                                }, {
                                    name: "skills",
                                    value: skillList
                                }).then(response => {
                                    console.log(response);
                                    action("goProject");
                                });
                            }
                        </script>

                        </html>
                    <?php
                    } else {
                        //Si estamos editando una actividad que ya existe
                        $id = $_SESSION['current_activity'];
                        //Obtenemos el nombre i la descripción de la actividad
                        $result = $conn->query("SELECT name, description FROM activities WHERE id = '$id'");
                        $row = $result->fetch_assoc();
                        $name = $row['name'];
                        $description = $row['description'];
                        //Obtenemos las skills de la actividad
                        $result = $conn->query("SELECT skill_id, percentage FROM act_skills WHERE activity_id = '$id'");
                        $actual_skills = [];
                        while ($row = $result->fetch_assoc()) {
                            $id = $row['skill_id'];
                            $percentage = $row['percentage'];
                            //Añadimos la skill junto al valor 100 como un objeto al array
                            array_push($actual_skills, (object) array('id' => $id, 'percentage' => $percentage));
                        }
                    ?>
                        <!DOCTYPE html>
                        <html lang="en">

                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <title>Edit <?php echo $name; ?></title>
                        </head>

                        <body>
                            <button onclick='action("goProject")'>back</button>
                            <input id="name" type="text" placeholder="name" value=<?php echo "\"$name\""; ?>>
                            <textarea id="description" placeholder="description"><?php echo $description; ?></textarea>
                            <div id="skills">
                                <?php
                                //Obtenemos las skills del proyecto
                                $result = $conn->query("SELECT skill_id FROM project_skills WHERE project_id = '{$_SESSION['current_project']}'");
                                //Recorremos las skills del proyecto
                                while ($row = $result->fetch_assoc()) {
                                    $id = $row['skill_id'];
                                    $possible = 0;
                                    foreach ($possible_act_skills as $key => $value) {
                                        if ($value->id == $id) {
                                            $possible = $value->possible;
                                        }
                                    }
                                    //Obtenemos el valor de la actual skill
                                    $actual = 0;
                                    foreach ($actual_skills as $key => $value2) {
                                        if ($value2->id == $id) {
                                            $actual = $value2->percentage;
                                        }
                                    }
                                    //Obtenemos el nombre i la imagen de la skill
                                    $result = $conn->query("SELECT name, src FROM skills WHERE id = '$id'");
                                    $row = $result->fetch_assoc();
                                    $name = $row['name'];
                                    $src = $row['src'];
                                    $path = "assets/skills/black/";
                                    //Imprimimos la skill
                                    echo "<div>";
                                    echo "<img src='$domain$path$src' alt='$name'>";
                                    echo "<p>$name</p>";
                                    echo "<input id='#$id#' type='range' min='0' max='100' value='$actual' block='" . ($possible + $actual) . "'>";
                                    echo "</div>";
                                }

                                ?>
                            </div>
                            <button onclick="saveActivity()">Save Activity</button>
                        </body>
                        <?php include_once "scripts.php"; ?>
                        <script>
                            function saveActivity() {
                                let name = document.getElementById("name").value;
                                let description = document.getElementById("description").value;
                                let skills = document.getElementById("skills").children;
                                var skillList = "";
                                for (let i = 0; i < skills.length; i++) {
                                    let skill = skills[i];
                                    let id = skill.children[2].id;
                                    let range = skill.children[2].value;
                                    skillList += id + ":" + range + ";";
                                }
                                fetchNispera({
                                    name: "action",
                                    value: "saveActivity"
                                }, {
                                    name: "name",
                                    value: name
                                }, {
                                    name: "description",
                                    value: description
                                }, {
                                    name: "skills",
                                    value: skillList
                                }).then(response => {
                                    console.log(response);
                                    action("goActivity");
                                });
                            }
                        </script>

                        </html>
                    <?php
                    }
                }
                break;
            case "saveActivity":
                if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                    $name = $data['name'];
                    $description = $data['description'];
                    $skills = $data['skills'];
                    $possible_act_skills = $_SESSION['possible_act_skills'];
                    if ($_SESSION['current_activity'] == "") {
                        $possible_skills = "false";
                        foreach ($possible_act_skills as $key => $value) {
                            if ($value->possible > 0) {
                                $possible_skills = "true";
                            }
                        }
                        if ($possible_skills === "true") {
                            //Si hay nombre y descripción
                            if ($name != "" && $description != "") {
                                //Si no existe una actividad con ese mismo nombre, la creamos
                                $result = $conn->query("SELECT id FROM activities WHERE name = '$name'");
                                if ($result->num_rows === 0) {
                                    //Creamos la actividad y obtenemos su id
                                    $conn->query("INSERT INTO activities (name,description,status,project_id) VALUES ('$name','$description','open','{$_SESSION['current_project']}')");
                                    $activityid = $conn->insert_id;
                                    //Obtenemos las possible_act_skills

                                    //Separamos la lista de skills en un array
                                    $skills = explode(";", $skills);
                                    //Recorremos el array
                                    foreach ($skills as $skill) {
                                        //Separamos el id de la skill y el rango
                                        $skill = explode(":", $skill);
                                        // Quita los # a la id
                                        $skillid = intval(str_replace("#", "", $skill[0]));
                                        $range = $skill[1];
                                        //Nos aseguramos que la actividad no tenga más del porcentaje posible de la skill
                                        foreach ($possible_act_skills as $key => $value) {
                                            if ($value->id == $skillid) {
                                                if ($range > $value->possible) {
                                                    $range = $value->possible;
                                                }
                                            }
                                        }
                                        if ($range > 0) {
                                            //Añadimos la skill a la actividad
                                            $conn->query("INSERT INTO act_skills (activity_id,skill_id,percentage) VALUES ('$activityid','$skillid','$range')");
                                        }
                                    }
                                } else {
                                    echo "This activity already exists";
                                }
                            } else {
                                echo "No name or description";
                            }
                        } else {
                            echo "No possible skills";
                        }
                    } else {
                        //Obtenemos las skills de la actividad actual
                        $result = $conn->query("SELECT skill_id, percentage FROM act_skills WHERE activity_id = '{$_SESSION['current_activity']}'");
                        //Recorremos las skills
                        while ($row = $result->fetch_assoc()) {
                            $id = $row['skill_id'];
                            $percentage = $row['percentage'];
                            //Encontramos el id en el array y le sumamos el porcentaje a possible
                            foreach ($possible_act_skills as $key => $value) {
                                if ($value->id == $id) {
                                    $possible_act_skills[$key]->possible += $percentage;
                                }
                            }
                        }
                        if ($name != "" && $description != "") {
                            //Si no existe una actividad con ese mismo nombre
                            $result = $conn->query("SELECT id FROM activities WHERE name = '$name'");
                            $alreadyexists = $result->num_rows;
                            //Obtenemos el nombre actual de la actividad
                            $result = $conn->query("SELECT name FROM activities WHERE id = '{$_SESSION['current_activity']}'");
                            $row = $result->fetch_assoc();
                            $oldname = $row['name'];
                            //Si el nombre actual es el mismo que el nuevo, no hay problema
                            if ($oldname === $name) {
                                $alreadyexists = 0;
                            }

                            if ($alreadyexists === 0) {
                                //Actualizamos la actividad con el nuevo nombre y la nueva descripción
                                $conn->query("UPDATE activities SET name = '$name', description = '$description' WHERE id = '{$_SESSION['current_activity']}'");

                                //Borramos las skills de la actividad
                                $conn->query("DELETE FROM act_skills WHERE activity_id = '{$_SESSION['current_activity']}'");

                                //Separamos la lista de skills en un array
                                $skills = explode(";", $skills);
                                //Recorremos el array
                                foreach ($skills as $skill) {
                                    //Separamos el id de la skill y el rango
                                    $skill = explode(":", $skill);
                                    // Quita los # a la id
                                    $skillid = intval(str_replace("#", "", $skill[0]));
                                    $range = $skill[1];
                                    //Nos aseguramos que la actividad no tenga más del porcentaje posible de la skill
                                    foreach ($possible_act_skills as $key => $value) {
                                        if ($value->id == $skillid) {
                                            if ($range > $value->possible) {
                                                $range = $value->possible;
                                            }
                                        }
                                    }
                                    if ($range > 0) {
                                        //Añadimos la skill a la actividad
                                        $conn->query("INSERT INTO act_skills (activity_id,skill_id,percentage) VALUES ('{$_SESSION['current_activity']}','$skillid','$range')");
                                    }
                                }
                            } else {
                                echo "This activity already exists";
                            }
                        } else {
                            echo "No name or description";
                        }
                    }
                }
                break;
            case "goActivity":
                if (isset($data['id'])) {
                    // Miramos si $_SESSION['id'] está en el proyecto
                    $result = $conn->query("SELECT COUNT(*) AS count FROM project_users WHERE user_id = '{$_SESSION['id']}' AND project_id = '{$_SESSION['current_project']}'");
                    $row = $result->fetch_assoc();
                    $count = $row['count'];
                    if ($count > 0) {
                        // Miramos si la actividad está en el proyecto
                        $result = $conn->query("SELECT COUNT(*) AS count FROM activities WHERE id = '{$data['id']}' AND project_id = '{$_SESSION['current_project']}'");
                        $row = $result->fetch_assoc();
                        $count = $row['count'];
                        if ($count > 0) {
                            $_SESSION['current_activity'] = $data['id'];
                        }
                    }
                }
                if ($_SESSION['current_activity'] != "") {
                    //Obtenemos el nombre de la actividad
                    $result = $conn->query("SELECT name, status, description, project_id FROM activities WHERE id = '{$_SESSION['current_activity']}'");
                    $row = $result->fetch_assoc();
                    $name = $row['name'];
                    $status = $row['status'];
                    $description = $row['description'];

                    //Obtenemos el nombre del proyecto
                    $result = $conn->query("SELECT name FROM projects WHERE id = '{$row['project_id']}'");
                    $row = $result->fetch_assoc();
                    $project_name = $row['name'];
                    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                    ?>
                        <!DOCTYPE html>
                        <html lang="en">

                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <title><?php echo $name; ?></title>
                        </head>

                        <body>
                            <button onclick="action('goProject')">back</button>
                            <button onclick="action('editActivity')">Edit Activity</button>
                            <h2><?php echo $project_name ?></h2>
                            <h1><?php echo $name ?></h1>
                            <p><?php echo $description ?></p>
                            <div id="skills">
                                <?php
                                //Obtenemos las skills de la actividad
                                $result = $conn->query("SELECT skill_id, percentage FROM act_skills WHERE activity_id = '{$_SESSION['current_activity']}'");
                                //Recorremos las skills
                                while ($row = $result->fetch_assoc()) {
                                    $id = $row['skill_id'];
                                    $percentage = $row['percentage'];
                                    //Obtenemos el nombre i la imagen de la skill
                                    $result2 = $conn->query("SELECT name, src FROM skills WHERE id = '$id'");
                                    $row2 = $result2->fetch_assoc();
                                    $name = $row2['name'];
                                    $src = $row2['src'];
                                    $path = "assets/skills/black/";
                                    //Imprimimos la skill
                                    echo "<div>";
                                    echo "<img src='$domain$path$src' alt='$name'>";
                                    echo "<p>$name</p>";
                                    echo "<input id='#$id#' type='range' min='0' max='100' value='$percentage'>";
                                    echo "</div>";
                                }
                                ?>
                            </div>
                            <h2>Teams</h2>
                            <button onclick="action('goTeams')">Teams</button>
                            <div id="teams">
                                <?php
                                //Obtenemos los equipos de la actividad
                                $result = $conn->query("SELECT id, name FROM teams WHERE activity_id = '{$_SESSION['current_activity']}'");
                                //Recorremos los equipos
                                while ($row = $result->fetch_assoc()) {
                                    $id = $row['id'];
                                    $name = $row['name'];
                                    //Obtenemos los miembros del equipo
                                    $result2 = $conn->query("SELECT user_id FROM teammembers WHERE team_id = '$id'");
                                    //Recorremos los miembros
                                    $members = [];
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $userid = $row2['user_id'];
                                        //Obtenemos el nombre del usuario
                                        $result3 = $conn->query("SELECT name, surname FROM users WHERE id = '$userid'");
                                        $row3 = $result3->fetch_assoc();
                                        $name2 = $row3['name'];
                                        $surname = $row3['surname'];
                                        array_push($members, (object) array('id' => $userid, 'name' => $name2, 'surname' => $surname));
                                    }
                                    //Obtenemos el número de miembros
                                    $members_count = count($members);
                                    //Si no hay miembros, borramos el team
                                    if ($members_count == 0) {
                                        $conn->query("DELETE FROM teams WHERE id = '$id'");
                                    } else {
                                        //Imprimimos el team
                                        echo "<div>";
                                        echo "<h3>$name</h3>";
                                        echo "<p>#$members_count members</p>";
                                        echo "<div class='members'>";
                                        foreach ($members as $member) {
                                            //Obtenemos la imagen del miembro a partir de su id y la función obtainImage
                                            echo "<img width='50px' height='50px' src='" . obtainImage($member->id) . "' alt=''>";
                                            echo "<p>$member->name $member->surname</p>";
                                            //Recorremos las skills de la actividad
                                            $result3 = $conn->query("SELECT id, skill_id FROM act_skills WHERE activity_id = '{$_SESSION['current_activity']}'");
                                            //Recorremos las skills
                                            while ($row3 = $result3->fetch_assoc()) {
                                                $act_skill_id = $row3['id'];
                                                $id = $row3['skill_id'];
                                                //Obtenemos el nombre i la imagen de la skill
                                                $result4 = $conn->query("SELECT name, src FROM skills WHERE id = '$id'");
                                                $row4 = $result4->fetch_assoc();
                                                $name = $row4['name'];
                                                $src = $row4['src'];
                                                $path = "assets/skills/black/";
                                                //Imprimimos la skill
                                                echo "<div>";
                                                echo "<img widht='20px' height='20px' src='$domain$path$src' alt='$name'>";
                                                echo "<p>$name</p>";
                                                //Observamos si el alumnos tiene una nota en esa act_skills_marks
                                                $result4 = $conn->query("SELECT mark FROM act_skills_marks WHERE act_skill_id = '$act_skill_id' AND user_id = '$member->id'");
                                                if ($result4->num_rows > 0) {
                                                    $row4 = $result4->fetch_assoc();
                                                    $mark = $row4['mark'];
                                                    echo "<input onchange='changeMark($act_skill_id,$member->id,this.value)' id='skill_mark' type='text' value='$mark'>";
                                                } else {
                                                    echo "<input onchange='changeMark($act_skill_id,$member->id,this.value)' id='skill_mark' type='text' >";
                                                }
                                                echo "</div>";
                                            }
                                        }
                                    }
                                }
                                ?>
                            </div>
                        </body>
                        <?php include_once "scripts.php"; ?>
                        <script>
                            function changeMark(act_skill_id, userid, mark) {
                                fetchNispera({
                                    name: "action",
                                    value: "changeMark"
                                }, {
                                    name: "act_skill_id",
                                    value: act_skill_id
                                }, {
                                    name: "userid",
                                    value: userid
                                }, {
                                    name: "mark",
                                    value: mark
                                }).then(response => {
                                    console.log(response);
                                });
                            }
                        </script>

                        </html>
                    <?php
                    }
                }
                break;
            case "deleteActivity":
                if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                    if (isset($data['id'])) {
                        // Miramos si $_SESSION['id'] está en el proyecto
                        $result = $conn->query("SELECT COUNT(*) AS count FROM project_users WHERE user_id = '{$_SESSION['id']}' AND project_id = '{$_SESSION['current_project']}'");
                        $row = $result->fetch_assoc();
                        $count = $row['count'];
                        if ($count > 0) {
                            // Miramos si la actividad está en el proyecto
                            $result = $conn->query("SELECT COUNT(*) AS count FROM activities WHERE id = '{$data['id']}' AND project_id = '{$_SESSION['current_project']}'");
                            $row = $result->fetch_assoc();
                            $count = $row['count'];
                            if ($count > 0) {
                                //Borramos act_skills
                                $conn->query("DELETE FROM act_skills WHERE activity_id = '{$data['id']}'");
                                //Borramos la actividad
                                $conn->query("DELETE FROM activities WHERE id = '{$data['id']}'");
                            }
                        }
                    }
                }
                break;
            case "goTeams":
                if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                    if ($_SESSION['current_activity'] != "") {
                        //Obtenemos el nombre de la actividad
                        $result = $conn->query("SELECT name FROM activities WHERE id = '{$_SESSION['current_activity']}'");
                        $row = $result->fetch_assoc();
                        $name = $row['name'];
                    ?>
                        <!DOCTYPE html>
                        <html lang="en">

                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <title><?php echo $name; ?> Teams</title>
                        </head>

                        <body>
                            <button onclick="action('goActivity')">back</button>
                            <button onclick="vaction('createTeam');action('goTeams')">Create Team</button>
                            <div id="teams">
                                <?php
                                //Obtenemos todos los teams que hay en la actividad
                                $result = $conn->query("SELECT id, name FROM teams WHERE activity_id = '{$_SESSION['current_activity']}'");
                                //Recorremos los teams
                                while ($row = $result->fetch_assoc()) {
                                    $id = $row['id'];
                                    $name = $row['name'];
                                    //Obtenemos los miembros del team
                                    $result2 = $conn->query("SELECT user_id FROM teammembers WHERE team_id = '$id'");
                                    //Recorremos los miembros
                                    $members = [];
                                    while ($row2 = $result2->fetch_assoc()) {
                                        $userid = $row2['user_id'];
                                        //Obtenemos el nombre del usuario
                                        $result3 = $conn->query("SELECT name, surname FROM users WHERE id = '$userid'");
                                        $row3 = $result3->fetch_assoc();
                                        $name2 = $row3['name'];
                                        $surname = $row3['surname'];
                                        array_push($members, (object) array('id' => $userid, 'name' => $name2, 'surname' => $surname));
                                    }
                                    //Obtenemos el número de miembros
                                    $members_count = count($members);
                                    //Imprimimos el team
                                    echo "<div>";
                                    echo "<input type='text' value='$name' onchange='teamName($id,this.value)'>";
                                    echo "<p>#$members_count members</p>";
                                    echo "<div class='members'>";
                                    foreach ($members as $member) {
                                        //Obtenemos la imagen del miembro a partir de su id y la función obtainImage
                                        echo "<img height='50px' width='50px' src='" . obtainImage($member->id) . "' alt=''>";
                                        echo "<p>$member->name $member->surname</p>";
                                        echo "<button onclick='deleteMember(\"$id\",\"$member->id\")'>Delete</button>";
                                    }
                                    echo "<button onclick='addMember(\"$id\")'>+</button>";
                                    echo "</div>";
                                    echo "</div>";
                                }
                                ?>
                            </div>
                            <div id="unteamed">

                            </div>
                        </body>
                        <?php include_once "scripts.php"; ?>
                        <script>
                            function addMember(teamid) {
                                fetchNispera({
                                    name: "action",
                                    value: "addMember"
                                }, {
                                    name: "teamid",
                                    value: teamid
                                }).then(response => {
                                    document.open();
                                    document.write(response);
                                    document.close();
                                });
                            };

                            function deleteMember(teamid, userid) {
                                fetchNispera({
                                    name: "action",
                                    value: "deleteMember"
                                }, {
                                    name: "teamid",
                                    value: teamid
                                }, {
                                    name: "userid",
                                    value: userid
                                });
                                action("goTeams");
                            };

                            function teamName(teamid, name) {
                                fetchNispera({
                                    name: "action",
                                    value: "teamName"
                                }, {
                                    name: "teamid",
                                    value: teamid
                                }, {
                                    name: "name",
                                    value: name
                                });
                            };
                        </script>

                        </html>
                    <?php
                    }
                }
                break;
            case "createTeam":
                //Si el usuario es admin o teacher
                if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                    if ($_SESSION['current_activity'] != "") {
                        //Creamos un nuevo team con el nombre "Unnamed Team"
                        $conn->query("INSERT INTO teams (name,activity_id) VALUES ('Unnamed Team','{$_SESSION['current_activity']}')");
                    }
                }
                break;
            case "addMember":
                //Si el usuario es admin o teacher
                if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                    if ($_SESSION['current_activity'] != "") {
                        //Obtenemos el id del team
                        $teamid = $data['teamid'];
                    ?>
                        <!DOCTYPE html>
                        <html lang="en">

                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <title>Unteamed students</title>
                        </head>

                        <body>
                            <button onclick="action('goTeams')">back</button>
                            <h1>Students without team</h1>
                            <?php
                            //Obtenemos los miembros del proyecto que no están en ningun team de la actividad current_activity y que sean students
                            $result = $conn->query("SELECT id, name, surname FROM users WHERE id IN (SELECT user_id FROM project_users WHERE project_id = '{$_SESSION['current_project']}') AND id NOT IN (SELECT user_id FROM teammembers WHERE team_id IN (SELECT id FROM teams WHERE activity_id = '{$_SESSION['current_activity']}')) AND role = 'student'");
                            //Recorremos los miembros
                            while ($row = $result->fetch_assoc()) {
                                $id = $row['id'];
                                $name = $row['name'];
                                $surname = $row['surname'];
                                //Imprimimos el miembro
                                echo "<div onclick='addtoTeam(\"$teamid\",\"$id\")'>";
                                echo "<img height='50px' width='50px' src='" . obtainImage($id) . "' alt=''>";
                                echo "<p>$name $surname</p>";
                                echo "</div>";
                            }
                            ?>

                        </body>
                        <?php include_once "scripts.php"; ?>
                        <script>
                            function addtoTeam(teamid, userid) {
                                fetchNispera({
                                    name: "action",
                                    value: "addtoTeam"
                                }, {
                                    name: "teamid",
                                    value: teamid
                                }, {
                                    name: "userid",
                                    value: userid
                                }).then(response => {
                                    console.log(response);
                                    action("goTeams");
                                });
                            };
                        </script>

                        </html>
<?php
                    }
                }
                break;
            case "addtoTeam":
                //Si el usuario es admin o teacher
                if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                    if ($_SESSION['current_activity'] != "") {
                        //Obtenemos el id del team
                        $teamid = $data['teamid'];
                        //Obtenemos el id del usuario
                        $userid = $data['userid'];
                        //Observamos si el usuario está ya en un team de la actividad y que sea student
                        $result = $conn->query("SELECT COUNT(*) AS count FROM teammembers WHERE user_id = '$userid' AND team_id IN (SELECT id FROM teams WHERE activity_id = '{$_SESSION['current_activity']}')");
                        $row = $result->fetch_assoc();
                        $count = $row['count'];
                        if ($count == 0) {
                            //Añadimos el usuario al team
                            $conn->query("INSERT INTO teammembers (team_id,user_id) VALUES ('$teamid','$userid')");
                        }
                    }
                }
                break;
            case "deleteMember":
                //Si el usuario es admin o teacher
                if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                    if ($_SESSION['current_activity'] != "") {
                        //Obtenemos el id del team
                        $teamid = $data['teamid'];
                        //Obtenemos el id del usuario
                        $userid = $data['userid'];
                        //Borramos el usuario del team
                        $conn->query("DELETE FROM teammembers WHERE team_id = '$teamid' AND user_id = '$userid'");
                    }
                }
                break;
            case "teamName":
                //Si el usuario es admin o teacher
                if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                    if ($_SESSION['current_activity'] != "") {
                        //Obtenemos el id del team
                        $teamid = $data['teamid'];
                        //Obtenemos el nombre del team
                        $name = $data['name'];
                        //Actualizamos el nombre del team
                        $conn->query("UPDATE teams SET name = '$name' WHERE id = '$teamid'");
                    }
                }
                break;
            case "changeMark":
                //Si el usuario es admin o teacher
                if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
                    if ($_SESSION['current_activity'] != "") {
                        //Obtenemos el id de la act_skill
                        $act_skill_id = $data['act_skill_id'];
                        //Obtenemos el id del usuario
                        $userid = $data['userid'];
                        //Obtenemos la nota
                        $mark = $data['mark'];

                        //Obtenemos el id de la actividad
                        $result = $conn->query("SELECT activity_id FROM act_skills WHERE id = '$act_skill_id'");
                        $row = $result->fetch_assoc();
                        $activity_id = $row['activity_id'];
                        //Obtenemos el id del proyecto
                        $result = $conn->query("SELECT project_id FROM activities WHERE id = '$activity_id'");
                        $row = $result->fetch_assoc();
                        $project_id = $row['project_id'];
                        //Verificamos que el usuario esté en el proyecto
                        $result = $conn->query("SELECT COUNT(*) AS count FROM project_users WHERE user_id = '{$_SESSION['id']}' AND project_id = '$project_id'");
                        $row = $result->fetch_assoc();
                        $count = $row['count'];
                        if ($count > 0) {
                            //Si la mark es 0 y existe una nota, la borramos
                            if ($mark == 0 || $mark == "") {
                                $conn->query("DELETE FROM act_skills_marks WHERE act_skill_id = '$act_skill_id' AND user_id = '$userid'");
                            }

                            //Si mark es un número más grande que 0 y más pequeño que 10 o igual
                            if (is_numeric($mark) && $mark > 0 && $mark <= 10) {
                                //Observamos si el usuario tiene una nota en esa act_skills_marks
                                $result = $conn->query("SELECT COUNT(*) AS count FROM act_skills_marks WHERE act_skill_id = '$act_skill_id' AND user_id = '$userid'");
                                $row = $result->fetch_assoc();
                                $count = $row['count'];
                                if ($count > 0) {
                                    //Actualizamos la nota
                                    $conn->query("UPDATE act_skills_marks SET mark = '$mark' WHERE act_skill_id = '$act_skill_id' AND user_id = '$userid'");
                                } else {
                                    //Añadimos la nota
                                    $conn->query("INSERT INTO act_skills_marks (act_skill_id,user_id,mark) VALUES ('$act_skill_id','$userid','$mark')");
                                }
                            }
                        }
                    }
                }
                break;
            default:
                echo 'Error: Acción no válida.';
                break;
        }
    }
} else {
    echo 'Error: Se esperaba una solicitud POST.';
}
