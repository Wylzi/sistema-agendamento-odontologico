<?php
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();

$clinicas = listarClinicas();
$meusHorarios = listarMeusHorarios($_SESSION['dentista_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap">
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Horários</title>
</head>
<body>
    <h1>Olá, <?= htmlspecialchars($_SESSION['dentista_nome']) ?></h1>

   <nav class="nav">
        <a href="dashboard.php">Ver agenda</a>
        <a href="gerenciar.php" class="ativo">Cadastrar horários</a>
    </nav>

    <div class="card">
        <h2>Cadastrar nova clínica</h2>

        <label for="nome_clinica">Nome da clínica</label>
        <input type="text" id="nome_clinica">

        <label for="endereco_clinica">Endereço (opcional)</label>
        <input type="text" id="endereco_clinica">

        <button id="btn-cadastrar-clinica">Adicionar clínica</button>

        <div id="area-resultado-clinica"></div>
    </div>

    <div class="card">
        <h2>Cadastrar horário disponível</h2>

        <label for="clinica_id">Clínica</label>
        <select id="clinica_id">
            <option value="">Selecione...</option>
            <?php foreach ($clinicas as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="data">Data</label>
        <input type="date" id="data">

        <label for="hora">Horário</label>
        <input type="time" id="hora">

        <label for="vagas">Vagas nesse horário</label>
        <input type="number" id="vagas" min="1" value="1">

        <button id="btn-cadastrar">Adicionar horário</button>

        <div id="area-resultado-cadastro"></div>
    </div>

    <div class="card">
        <h2>Meus horários cadastrados</h2>

        <?php if (empty($meusHorarios)): ?>
            <p>Nenhum horário cadastrado ainda.</p>
        <?php else: ?>
            <?php foreach ($meusHorarios as $h): ?>
                <div class="horario-linha">
                    <div class="horario-linha-info">
                        <span class="horario-linha-data"><?= htmlspecialchars(formatarDataBr($h['data'])) ?> · <?= htmlspecialchars(formatarHoraBr($h['hora'])) ?></span>
                        <span class="horario-linha-clinica"><?= htmlspecialchars($h['clinica_nome']) ?></span>
                    </div>
                    <span class="horario-linha-vagas"><?= (int) $h['vagas_ocupadas'] ?>/<?= (int) $h['vagas_totais'] ?></span>
                    <button type="button" class="btn-remover" data-id="<?= (int) $h['id'] ?>" data-vagas-ocupadas="<?= (int) $h['vagas_ocupadas'] ?>">Remover</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <p><a href="logout.php" class="link-sair">Sair</a></p>

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

        document.getElementById('btn-cadastrar').addEventListener('click', function () {
            const clinicaId = document.getElementById('clinica_id').value;
            const data = document.getElementById('data').value;
            const hora = document.getElementById('hora').value;
            const vagas = document.getElementById('vagas').value;
            const resultado = document.getElementById('area-resultado-cadastro');
            const botao = this;

            if (!clinicaId) {
                resultado.innerHTML = '<p>Selecione uma clínica.</p>';
                return;
            }

            if (!data || !hora) {
                resultado.innerHTML = '<p>Informe data e horário.</p>';
                return;
            }

            botao.disabled = true;
            botao.textContent = 'Salvando...';

            const dados = new FormData();
            dados.append('clinica_id', clinicaId);
            dados.append('data', data);
            dados.append('hora', hora);
            dados.append('vagas', vagas);

            fetch('cadastrar_horario.php', {
                method: 'POST',
                body: dados
            })
                .then(resposta => resposta.json())
                .then(resposta => {
                    botao.disabled = false;
                    botao.textContent = 'Adicionar horário';

                    if (resposta.sucesso) {
                        resultado.innerHTML = '<p>Horário cadastrado!</p>';
                        document.getElementById('data').value = '';
                        document.getElementById('hora').value = '';
                        document.getElementById('vagas').value = '1';
                    } else {
                        resultado.innerHTML = '<p>Erro: ' + resposta.erro + '</p>';
                    }
                });
        });

        document.querySelectorAll('.btn-remover').forEach(botao => {
            botao.addEventListener('click', function () {
                const horarioId = this.dataset.id;
                const vagasOcupadas = parseInt(this.dataset.vagasOcupadas, 10);
                const linha = this.closest('.horario-linha');

                let mensagem = 'Remover esse horário?';
                if (vagasOcupadas > 0) {
                    mensagem = `Esse horário tem ${vagasOcupadas} agendamento(s) vinculado(s). Ao remover, esses agendamentos também serão apagados. Continuar?`;
                }

                if (!confirm(mensagem)) {
                    return;
                }

                this.disabled = true;
                this.textContent = 'Removendo...';

                const dados = new FormData();
                dados.append('horario_id', horarioId);

                fetch('remover_horario.php', {
                    method: 'POST',
                    body: dados
                })
                    .then(resposta => resposta.json())
                    .then(resposta => {
                        if (resposta.sucesso) {
                            linha.remove();
                        } else {
                            alert('Erro: ' + resposta.erro);
                            this.disabled = false;
                            this.textContent = 'Remover';
                        }
                    });
            });
        });
    </script>
</body>
</html>