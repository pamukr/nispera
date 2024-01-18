<script>
    function fetchNispera(...attributes) {
        return new Promise((resolve, reject) => {
            let data = {};
            attributes.forEach(attribute => {
                data[attribute.name] = attribute.value;
            });
            fetch('nispera.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.text()) // Convertir la respuesta a texto
                .then(responseText => {
                    resolve(responseText); // Resolvemos la promesa con el responseText
                })
                .catch(error => {
                    reject(error); // Rechazamos la promesa en caso de error
                });
        });
    }

    function vaction(param) {
        fetchNispera({
            name: "action",
            value: param
        });
    }

    function action(param) {
        fetchNispera({
            name: "action",
            value: param
        }).then(response => {
            document.open();
            document.write(response);
            document.close();
        });
    }

    function logOut() {
        localStorage.removeItem("token");
        fetchNispera({
            name: "action",
            value: "logout"
        })
        location.reload();
    }

    function editProfile() {
        let profileDiv = document.getElementById("edit-profile");
        let hrElement = profileDiv.querySelector("hr.user");
        let main = document.getElementById("main");

        if (profileDiv.classList.contains("height-0")) {
            hrElement.style.display = "block";
            profileDiv.classList.remove("height-0");
            main.style.display = "none";
        } else {
            profileDiv.classList.add("height-0");
            hrElement.style.display = "none";
            main.style.display = "block";
        }
    }

    function updateImgPreview(input) {
        let label = input.nextElementSibling;

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                label.style.backgroundImage = `url('${e.target.result}')`;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function changePassword() {
        let currentPass = document.getElementById("user-current-password").value;
        let newPass = document.getElementById("user-new-password").value;
        fetchNispera({
            name: "action",
            value: "changePassword"
        }, {
            name: "current",
            value: currentPass
        }, {
            name: "new",
            value: newPass
        }).then(response => {
            editProfile();
            if (response == "true") {
                showStatus(true, "Password changed");
            } else {
                showStatus(false, "Incorrect password");
            }
        });
    }

    async function wait(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    async function showStatus(status, message) {
        const messageLog = document.createElement('div');
        messageLog.classList.add('messageLog');
        let color = "";

        if (status) {
            status = 'bx-check';
            color = "green";
        } else {
            status = 'bx-x';
            color = "red";
        }
        messageLog.innerHTML = `<div><i class='bx ${status}'></i><p class='${color}'>${message}</p></div>`;
        document.body.appendChild(messageLog);
        await wait(3000);
        messageLog.style.opacity = '0';
        await wait(1000);
        messageLog.remove();
    }

    function changePP(id) {
        //Creamos un form y ponemos la imagen
        let pp = document.getElementById("pp").files[0];
        let data = new FormData();
        data.append('pp', pp);
        data.append('changePP', 'true');
        data.append('userid', id);
        fetch('nispera.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.text())
            .then(content => {
            });
    }
</script>