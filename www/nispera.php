<?php
include_once 'db_connect.php';
// Verificar si se ha enviado una solicitud POST
if (!isset($_SESSION)) {
    session_start();
}

//Variables generals
$domain = "http://localhost:8080/";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                    </head>

                    <body>
                        <h1 id="uwu">Bienvenido <?php echo $_SESSION['username'] ?></h1>
                        <button onclick="logOut()">Log out</button>
                        <button onclick="createProject()">Create a new project</button>
                        <div id="projects">
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
                                echo "<button onclick='goProject($projectid)'>$project</button>";
                            }
                            ?>
                        </div>
                        <?php
                        if ($_SESSION['role'] === 'admin') {
                            echo '<input type="file" id="import">';
                            echo "<button onclick='action(\"goUsers\")'>Users</button>";
                        }
                        ?>
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
                                        echo "incorrect_skills";
                                        //S'elimina el projecte amb id $projectid
                                        $conn->query("DELETE FROM project_users WHERE project_id = '$projectid'");
                                        $conn->query("DELETE FROM projects WHERE id = '$projectid'");
                                    }
                                } else {
                                    echo "incorrect_skills";
                                    //S'elimina el projecte amb id $projectid
                                    $conn->query("DELETE FROM project_users WHERE project_id = '$projectid'");
                                    $conn->query("DELETE FROM projects WHERE id = '$projectid'");
                                }
                            } else {
                                echo "nom_incorrecte";
                            }
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
                        </head>

                        <body>
                            <button onclick='action("main")'>back</button>
                            <input type="text" id="name" placeholder="name" value=<?php echo '"' . $_SESSION['current_name'] . '"'; ?>>
                            <button onclick="addPeople()">Add people</button>
                            <button onclick="addSkill()">New Skill</button>
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

                            <button onclick="create()">Create Project</button>
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
                                        let id = skill.children[2].id;
                                        let range = skill.children[2].value;
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
                                    console.log(response);
                                    //Si la respuesta es true, entonces se ha creado el proyecto
                                    if (response === "true") {
                                        action("main");
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
                        $name = $row['name'];
                    ?>
                        <!DOCTYPE html>
                        <html lang="en">

                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <title><?php echo  $name; ?></title>
                        </head>

                        <body>
                            <h1><?php echo  $name; ?></h1>
                            <button onclick="action('main')">back</button>
                            <button onclick="action('editProject')">Edit project</button>
                            <button onclick="action('goSkills')">Skills</button>
                            <button onclick="action('addPeople')">Add people</button>
                            <h1>Activities</h1>
                            <button onclick="action('editActivity')">New activity</button>
                            <div id="activities">
                                <?php
                                //Obtenemos las actividades del proyecto
                                $result = $conn->query("SELECT id, name, status FROM activities WHERE project_id = '{$_SESSION['current_project']}'");
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
                        <button onclick="action('addUser')">New User</button>
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
                    $id = $data['id'];
                    $_SESSION['current_user'] = $id;
                    $result = $conn->query("SELECT name, surname, user, role, email FROM users WHERE id = '$id'");
                    $row = $result->fetch_assoc();
                    $name = $row['name'];
                    $surname = $row['surname'];
                    $user = $row['user'];
                    $role = $row['role'];
                    $email = $row['email'];
                ?>
                    <!DOCTYPE html>
                    <html lang="en">

                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Edit User <?php echo $username; ?></title>
                    </head>

                    <body>
                        <button onclick="action('goUsers')">back</button>
                        <input type="file" id="pp">
                        <input type="text" id="name" placeholder="name" value=<?php echo '"' . $name . '"'; ?>>
                        <input type="text" id="surname" placeholder="surname" value=<?php echo '"' . $surname . '"'; ?>>
                        <br>
                        <input type="mail" id="email" placeholder="email" value=<?php echo '"' . $email . '"'; ?>>
                        <input type="text" id="user" placeholder="user" value=<?php echo '"' . $user . '"'; ?>>
                        <input type="text" id="role" placeholder="role" value=<?php echo '"' . $role . '"'; ?>>
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
                            let pp = document.getElementById("pp").files[0];
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
                            }, {
                                name: "pp",
                                value: pp
                            }).then(response => {
                                console.log(response);
                                action("goUsers");
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
                    $pp = $data['pp'];

                    if ($_SESSION['current_user'] = "") {
                        $conn->query("INSERT INTO users (id,name,surname,user,role,email) VALUES ('$id','$name','$surname','$user','$role','$email')");
                    } else {
                        $conn->query("UPDATE users SET name = '$name', surname = '$surname', user = '$user', role = '$role', email = '$email' WHERE id = '{$_SESSION['current_user']}'");
                    }
                    //Si el usuario no tiene foto de perfil, le ponemos una por defecto
                    $result = $conn->query("SELECT COUNT(*) AS count FROM user_images WHERE user_id = '$id'");
                    $row = $result->fetch_assoc();
                    $count = $row['count'];
                    if ($count == 0) {
                        $conn->query("INSERT INTO user_images (user_id,src) VALUES ('$id','default.png')");
                    }
                    //Si el usuario tiene una foto de perfil, la actualizamos
                    if ($pp != "") {
                        $conn->query("UPDATE user_images SET src = '$pp' WHERE user_id = '$id'");
                    }
                    //Si el usuario tiene grupos, los actualizamos
                    if ($groups != "") {
                        //Separamos los grupos en un array
                        $groups = explode(",", $groups);
                        //Recorremos el array
                        foreach ($groups as $group) {
                            //Obtenemos el id del grupo
                            $result = $conn->query("SELECT id FROM `groups` WHERE name = '$group'");
                            $row = $result->fetch_assoc();
                        }
                    }
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
                    </head>

                    <body>
                        <button onclick='savePeople()'>back</button>
                        <h1>Users</h1>
                        <h2>Teachers</h2>
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
                                if (strpos($_SESSION['current_people'], "#" . $id . "#") !== false) {
                                    echo "<input type='checkbox' name='users' value='#$id#' checked>$name $surname<br>";
                                } else {
                                    echo "<input type='checkbox' name='users' value='#$id#'>$name $surname<br>";
                                }
                            }
                        }
                        ?>
                        <h2>Students</h2>
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
                                if (strpos($_SESSION['current_people'], "#" . $id . "#") !== false) {
                                    echo "<input type='checkbox' name='users' value='#$id#' checked>$name $surname<br>";
                                } else {
                                    echo "<input type='checkbox' name='users' value='#$id#'>$name $surname<br>";
                                }
                            }
                        }
                        ?>
                    </body>
                    <?php include_once "scripts.php"; ?>
                    <script>
                        function savePeople() {
                            let users = document.getElementsByName("users");
                            let people = "";
                            users.forEach(user => {
                                if (user.checked) {
                                    people += user.value + ";";
                                }
                            });
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
                </head>

                <body>
                    <?php if (isset($data['create'])) { ?>
                        <button onclick='action("createProject")'>back</button>
                    <?php } else { ?>
                        <button onclick='action("goSkills")'>back</button>
                    <?php } ?>
                    <?php
                    $imagePath = "assets/skills/color";
                    $files = scandir($imagePath);
                    foreach ($files as $file) {
                        $imageUrl = $domain . $imagePath . "/" . $file;
                        $filename = explode(".", $file)[0];
                        if ($filename) {
                            echo "<label>";
                            echo "<input type='radio' name='skill-image' value='$file'>";
                            echo "<img src='$imageUrl' alt='$filename'>";
                            echo "</label>";
                        }
                    }
                    ?>
                    <input id="name" type="text" value="SkillName">
                    <input id="skill-range" type="range" min="0" max="100">
                    <button onclick="saveSkill()">Save Skill</button>
                </body>
                <?php include_once "scripts.php"; ?>
                <script>
                    function saveSkill() {
                        let name = document.getElementById("name").value;
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
                                    action("goProject");
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
                            <button onclick="">Teams</button>
                            <div id="teams">

                            </div>
                        </body>

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
            default:
                echo 'Error: Acción no válida.';
                break;
        }
    }
} else {
    echo 'Error: Se esperaba una solicitud POST.';
}
