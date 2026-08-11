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
                    <option value="2">Clínica Bem Estar Odonto</option>
                    <option value="1">Clínica Sorriso Total</option>
            </select>
    <div id="area-horarios"></div>

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
                    let html = '<ul>';
                    horarios.forEach(h => {
                        html += `<li>${h.data} às ${h.hora} - ${h.dentista_nome} (${h.vagas_totais - h.vagas_ocupadas} vagas)</li>`;
                    });
                    html += '</ul>';
                    area.innerHTML = html;
                });
        });
    </script>
</body>
</html>