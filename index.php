<?php
require_once __DIR__ . '/includes/functions.php';

$clinicas = listarClinicas();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Agendar Consulta</title>
</head>
<body>
    <h1>Agendamento Odontológico</h1>

    <label for="clinica_id">Escolha a clínica</label>
    <select id="clinica_id">
        <option value="">Selecione...</option>
        <?php foreach ($clinicas as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
        <?php endforeach; ?>
    </select>

    <div id="area-horarios"></div>

    <label for="ficha_numero">Número da ficha</label>
    <input type="text" id="ficha_numero">

    <button id="btn-confirmar">Confirmar agendamento</button>

    <div id="area-resultado"></div>

    <script>
        document.getElementById('clinica_id').addEventListener('change', function () {
            const clinicaId = this.value;
            const area = document.getElementById('area-horarios');
            if (!clinicaId) {
                area.innerHTML = '';
                return;
            }
            fetch('horarios.php?clinica_id=' + clinicaId)
                .then(resposta => resposta.json())
                .then(horarios => {
                    if (horarios.length === 0) {
                        area.innerHTML = '<p>Nenhum horário disponível nessa clínica.</p>';
                        return;
                    }
                    let html = '';
                    horarios.forEach(h => {
                        const vagasRestantes = h.vagas_totais - h.vagas_ocupadas;
                        html += `
                            <label>
                                <input type="radio" name="horario_id" value="${h.id}">
                                ${h.data} às ${h.hora} - ${h.dentista_nome} (${vagasRestantes} vagas)
                            </label><br>
                        `;
                    });
                    area.innerHTML = html;
                });
        });

        document.getElementById('btn-confirmar').addEventListener('click', function () {
            const horarioSelecionado = document.querySelector('input[name="horario_id"]:checked');
            const fichaNumero = document.getElementById('ficha_numero').value.trim();
            const resultado = document.getElementById('area-resultado');

            if (!horarioSelecionado) {
                resultado.innerHTML = '<p>Selecione um horário.</p>';
                return;
            }

            if (fichaNumero === '') {
                resultado.innerHTML = '<p>Informe o número da ficha.</p>';
                return;
            }

            const dados = new FormData();
            dados.append('horario_id', horarioSelecionado.value);
            dados.append('ficha_numero', fichaNumero);

            fetch('agendar.php', {
                method: 'POST',
                body: dados
            })
                .then(resposta => resposta.json())
                .then(resultadoAgendamento => {
                    if (resultadoAgendamento.sucesso) {
                        resultado.innerHTML = '<p>Consulta confirmada!</p>';
                    } else {
                        resultado.innerHTML = '<p>Erro: ' + resultadoAgendamento.erro + '</p>';
                    }
                });
        });
    </script>
</body>
</html>