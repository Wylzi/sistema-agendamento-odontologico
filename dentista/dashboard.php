<?php
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();

$clinicas = listarClinicas();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Agenda</title>
</head>
<body>
    <h1>Olá, <?= htmlspecialchars($_SESSION['dentista_nome']) ?></h1>
    <p><a href="logout.php">Sair</a></p>

    <h2>Cadastrar nova clínica</h2>

    <label for="nome_clinica">Nome da clínica</label><br>
    <input type="text" id="nome_clinica"><br>

    <label for="endereco_clinica">Endereço (opcional)</label><br>
    <input type="text" id="endereco_clinica"><br>

    <button id="btn-cadastrar-clinica">Adicionar clínica</button>

    <div id="area-resultado-clinica"></div>

    <hr>

    <h2>Cadastrar horário disponível</h2>

    <label for="clinica_id">Clínica</label><br>
    <select id="clinica_id">
        <option value="">Selecione...</option>
        <?php foreach ($clinicas as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
        <?php endforeach; ?>
    </select><br>

    <label for="data">Data</label><br>
    <input type="date" id="data"><br>

    <label for="hora">Horário</label><br>
    <input type="time" id="hora"><br>

    <label for="vagas">Vagas nesse horário</label><br>
    <input type="number" id="vagas" min="1" value="1"><br>

    <button id="btn-cadastrar">Adicionar horário</button>

    <div id="area-resultado-cadastro"></div>

    <script>
        document.getElementById('btn-cadastrar-clinica').addEventListener('click', function () {
            const nome = document.getElementById('nome_clinica').value.trim();
            const endereco = document.getElementById('endereco_clinica').value.trim();
            const resultado = document.getElementById('area-resultado-clinica');
            const selectClinica = document.getElementById('clinica_id');
            const botao = this;

            if (nome === '') {
                resultado.innerHTML = '<p>Informe o nome da clínica.</p>';
                return;
            }

            botao.disabled = true;
            botao.textContent = 'Salvando...';

            const dados = new FormData();
            dados.append('nome', nome);
            dados.append('endereco', endereco);

            fetch('cadastrar_clinica.php', {
                method: 'POST',
                body: dados
            })
                .then(resposta => resposta.json())
                .then(resposta => {
                    botao.disabled = false;
                    botao.textContent = 'Adicionar clínica';

                    if (resposta.sucesso) {
                        resultado.innerHTML = '<p>Clínica cadastrada!</p>';

                        const novaOpcao = document.createElement('option');
                        novaOpcao.value = resposta.id;
                        novaOpcao.textContent = nome;
                        selectClinica.appendChild(novaOpcao);

                        document.getElementById('nome_clinica').value = '';
                        document.getElementById('endereco_clinica').value = '';
                    } else {
                        resultado.innerHTML = '<p>Erro: ' + resposta.erro + '</p>';
                    }
                });
        });
    </script>
</body>
</html>