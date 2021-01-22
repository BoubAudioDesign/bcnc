<?php
require_once "config.php";

if (isset($_GET['seek'])) {
    $seek = $_GET['seek'];
    $lines = [];
    $handle = fopen($logFile, 'rb');

    if ($seek > 0) {
        fseek($handle, $seek);
    }

    while (($line = fgets($handle, 4096)) !== false) {
        $lines[] = $line;
    }

    $seek = ftell($handle);

    header("Content-Type: application/json");
    echo json_encode(['seek' => $seek, 'lines' => $lines]);

    exit();
} 
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Logs</title>
    <style type="text/css">
        body {
            margin: 0;
            padding: 0;
        }
        #app {
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            color: #333;
        }
    </style>
</head>
<body>
    <div id="app">
        <div v-for="line in lines">{{ line }}</div>
    </div>

    <script src="https://unpkg.com/vue/dist/vue.min.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script>
        const app = new Vue({
            el: '#app',
            data: {
                lines: [],
                seek: 0
            },
            mounted () {
                this.load();
                setInterval(this.load, 3000);
            },
            methods: {
                load () {
                    axios.get(`?seek=${this.seek}`)
                        .then(response => {
                            this.lines = [...response.data.lines.reverse(), ...this.lines];
                            this.seek = response.data.seek;
                        });
                }
            }
        });
    </script>
</body>
</html>