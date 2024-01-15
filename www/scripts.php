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
</script>