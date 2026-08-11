<?php
require_once __DIR__ . '/includes/functions.php';

$clinicas = listarClinicas();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap">
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        function diaDaSemana(dataIso) {
            const dias = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
            const [ano, mes, dia] = dataIso.split('-').map(Number);
            const data = new Date(ano, mes - 1, dia);
            return dias[data.getDay()];
        }

        function formatarDataExtenso(dataIso) {
            const meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
            const [ano, mes, dia] = dataIso.split('-').map(Number);
            return `${dia} de ${meses[mes - 1]}`;
        }

        const selectClinica = document.getElementById('clinica_id');
        const areaHorarios = document.getElementById('area-horarios');
        const campoFicha = document.getElementById('ficha_numero');
        const botaoConfirmar = document.getElementById('btn-confirmar');
        const areaResultado = document.getElementById('area-resultado');

        selectClinica.addEventListener('change', function () {
            const clinicaId = this.value;

            if (!clinicaId) {
                areaHorarios.innerHTML = '';
                return;
            }

            areaHorarios.innerHTML = '<p>Carregando horários...</p>';

            fetch('horarios.php?clinica_id=' + clinicaId)
                .then(resposta => resposta.json())
                .then(horarios => {
                    if (horarios.length === 0) {
                        areaHorarios.innerHTML = '<p>Nenhum horário disponível nessa clínica.</p>';
                        return;
                    }

                    let html = '';
                    let dataAnterior = null;

                    horarios.forEach(h => {
                        if (h.data !== dataAnterior) {
                            html += `
                                <div class="grupo-dia">
                                    <p class="dia-semana">${diaDaSemana(h.data)}</p>
                                    <p class="data-numero">${formatarDataExtenso(h.data)}</p>
                                </div>
                            `;
                            dataAnterior = h.data;
                        }

                        const vagasRestantes = h.vagas_totais - h.vagas_ocupadas;
                        const textoVagas = vagasRestantes === 1 ? 'vaga' : 'vagas';

                        html += `
                            <label class="horario-card">
                                <span class="horario-card-info">
                                    <input type="radio" name="horario_id" value="${h.id}">
                                    <span class="horario-hora">${h.hora.substring(0, 5)}</span>
                                </span>
                                <span class="chip-vagas">${vagasRestantes} ${textoVagas}</span>
                            </label>
                        `;
                    });

                    areaHorarios.innerHTML = html;
                });
        });

        // Reage quando o paciente escolhe (ou troca) um horário
        areaHorarios.addEventListener('change', function (evento) {
            if (evento.target.name !== 'horario_id') {
                return;
            }

            document.querySelectorAll('.horario-card').forEach(card => {
                card.classList.remove('selecionado');
            });
            evento.target.closest('.horario-card').classList.add('selecionado');

            // Se o botão estava travado por causa de um agendamento anterior, libera de novo
            botaoConfirmar.disabled = false;
            botaoConfirmar.textContent = 'Confirmar agendamento';
            areaResultado.innerHTML = '';
        });

        botaoConfirmar.addEventListener('click', function () {
            const horarioSelecionado = document.querySelector('input[name="horario_id"]:checked');
            const fichaNumero = campoFicha.value.trim();

            if (!horarioSelecionado) {
                areaResultado.innerHTML = '<p>Selecione um horário.</p>';
                return;
            }

            if (fichaNumero === '') {
                areaResultado.innerHTML = '<p>Informe o número da ficha.</p>';
                return;
            }

            botaoConfirmar.disabled = true;
            botaoConfirmar.textContent = 'Enviando...';

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
                        areaResultado.innerHTML = '<p>Consulta confirmada!</p>';
                        campoFicha.value = '';
                        botaoConfirmar.textContent = 'Consulta confirmada';
                        // botão permanece desabilitado até o paciente escolher outro horário
                    } else {
                        areaResultado.innerHTML = '<p>Erro: ' + resultadoAgendamento.erro + '</p>';
                        botaoConfirmar.disabled = false;
                        botaoConfirmar.textContent = 'Confirmar agendamento';
                    }
                });
        });
    </script>
</body>
</html>