<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Chamados</title>

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<div class="sidebar">

    <div class="logo">
        📡 Gestão TI
    </div>

    <div class="menu">
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="colaboradores.php">👥 Colaboradores</a>
        <a href="ativos.php">💻 Ativos</a>
        <a href="estoque.php">📦 Estoque</a>
        <a href="chamados.php" class="active">🎫 Chamados</a>
        <a href="centros_de_custo.php">🏢 Centros de Custo</a>
        <a href="relatorios.php">📈 Relatórios</a>
        <a href="configuracoes.php">⚙ Configurações</a>
    </div>
    

</div>

    

<div class="content">

<h1>Chamados</h1>

<div class="toolbar">
    <button onclick="abrirFormularioChamado()">Chamado</button>
    <button onclick="abrirImportacao()">📥 Importar</button>
    <button onclick="mostrarHistorico()">📚 Histórico </button>
      <button onclick="mostrarAbertos()"> ✅ Abertos</button>
       <form action="" method="get" style="display: flex; gap: 10px; flex: 1;">
        <input type="text" id="pesquisaChamado" name="search" placeholder="Pesquisar por número, solicitante...">
        <button type="button" onclick="pesquisarChamados()" style="padding: 12px 20px; cursor: pointer;">🔍 Pesquisar</button>
    </form>
        <button onclick="history.back()">Voltar</button>
    


  </div>  

<!-- Modal de Novo Chamado -->
<div id="modalChamado" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Novo Chamado</h2>
            <span class="fechar" onclick="fecharFormularioChamado()">&times;</span>
        </div>

        <form id="formChamado" class="form-chamado">
            <div class="form-group">
                <label for="numeroChamado">Nº do Chamado</label>
                <input type="text" id="numeroChamado" name="numeroChamado" placeholder="Digite ou deixe o padrão">
            </div>

            <div class="form-group">
                <label for="dataAbertura">Data de Abertura</label>
                <input type="datetime-local" id="dataAbertura" name="dataAbertura" readonly>
            </div>

            <div class="form-group">
                <label for="solicitante">Solicitante</label>
                <input type="text" id="solicitante" name="solicitante" placeholder="Digite o nome do solicitante" required>
            </div>

            <div class="form-group">
                <label for="tipoSolicitacao">Tipo de Solicitação</label>
                <select id="tipoSolicitacao" name="tipoSolicitacao" required>
                    <option value="">Selecione uma opção</option>
                    <option value="pedido_aparelho">📱 Pedido de Aparelho</option>
                    <option value="linha_nova">📞 Solicitação de Linha Nova</option>
                    <option value="problema_linha">⚠️ Problema na Linha</option>
                </select>
            </div>

            <div class="form-group">
                <label for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao" placeholder="Digite a descrição do chamado" rows="4"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Criar Chamado</button>
                <button type="button" class="btn-cancel" onclick="fecharFormularioChamado()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Importação -->
<div id="modalImportacao" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Importar Chamados em Massa</h2>
            <span class="fechar" onclick="fecharImportacao()">&times;</span>
        </div>

        <form id="formImportacao" class="form-importacao">
            <div class="form-group">
                <label for="arquivoImportacao">Selecione o arquivo CSV ou Excel</label>
                <input type="file" id="arquivoImportacao" name="arquivo" accept=".csv,.xlsx,.xls" required>
                <p style="font-size: 12px; color: #64748b; margin-top: 8px;">
                    📋 Formato esperado: Número do Chamado, Solicitante, Tipo de Solicitação, Data de Abertura, Descrição
                </p>
            </div>

            <div class="form-group">
                <label>Tipos de Solicitação Aceitos:</label>
                <ul style="font-size: 13px; color: #64748b; margin-left: 20px;">
                    <li>pedido_aparelho</li>
                    <li>linha_nova</li>
                    <li>problema_linha</li>
                </ul>
            </div>

            <div id="previewImportacao" style="display: none; margin: 15px 0; padding: 15px; background: #f1f5f9; border-radius: 8px;">
                <h4 style="margin-bottom: 10px;">Preview dos dados:</h4>
                <div id="previewConteudo" style="max-height: 200px; overflow-y: auto;"></div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Importar Chamados</button>
                <button type="button" class="btn-cancel" onclick="fecharImportacao()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<table class="table-chamados">

<thead>
<tr>
    <th>Número</th>
    <th>Solicitante</th>
    <th>Tipo</th>
    <th>Data de Abertura</th>
    <th>Status</th>
    <th>Ações</th>
</tr>
</thead>

<tbody id="tabelaChamados">
<tr>
    <td colspan="6" style="text-align: center;">
        Nenhum chamado registrado
    </td>
</tr>
</tbody>

</table>

</div>

<script>
// URL da API
const API_URL = 'api_chamados.php';

// Armazenar chamados (sincronizado com servidor)
let chamadosAbertos = [];
// Sincronizar com servidor ao carregar
async function sincronizarDados() {
    try {
        const response = await fetch(`${API_URL}?acao=listar`);
        if (response.ok) {
            chamadosAbertos = await response.json();
            localStorage.setItem('chamadosAbertos', JSON.stringify(chamadosAbertos));
      const chamadosVisiveis =
    chamadosAbertos.filter(
        chamado => chamado.status !== 'Fechado'
    );

exibirChamados(chamadosVisiveis);
        }
    } catch (erro) {
        console.log('Erro ao sincronizar: ', erro);
        // Usar dados do localStorage se servidor não responder
        chamadosAbertos = JSON.parse(localStorage.getItem('chamadosAbertos')) || [];
        exibirChamados();
    }
}

function abrirFormularioChamado() {
    const modal = document.getElementById('modalChamado');
    modal.style.display = 'flex';
    
    // Gerar número do chamado automaticamente (editável)
    const numeroChamado = 'CHD-' + Date.now();
    document.getElementById('numeroChamado').value = numeroChamado;
    
    // Definir data e hora de abertura
    const agora = new Date();
    const ano = agora.getFullYear();
    const mes = String(agora.getMonth() + 1).padStart(2, '0');
    const dia = String(agora.getDate()).padStart(2, '0');
    const hora = String(agora.getHours()).padStart(2, '0');
    const minuto = String(agora.getMinutes()).padStart(2, '0');
    
    document.getElementById('dataAbertura').value = `${ano}-${mes}-${dia}T${hora}:${minuto}`;
}

function fecharFormularioChamado() {
    const modal = document.getElementById('modalChamado');
    modal.style.display = 'none';
    document.getElementById('formChamado').reset();
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modal = document.getElementById('modalChamado');
    if (event.target == modal) {
        fecharFormularioChamado();
    }
}

// Mapear tipos de solicitação
const tiposMap = {
    'pedido_aparelho': '📱 Pedido de Aparelho',
    'linha_nova': '📞 Solicitação de Linha Nova',
    'problema_linha': '⚠️ Problema na Linha'
};

// Exibir chamados na tabela
function exibirChamados(chamados = chamadosAbertos) {
    const tabelaChamados = document.getElementById('tabelaChamados');
    
    if (chamados.length === 0) {
        tabelaChamados.innerHTML = '<tr><td colspan="6" style="text-align: center;">Nenhum chamado registrado</td></tr>';
        return;
    }
    
    tabelaChamados.innerHTML = chamados.map((chamado, index) => {
        const dataFormatada = new Date(chamado.dataAbertura).toLocaleString('pt-BR');
        const tipo = tiposMap[chamado.tipoSolicitacao] || chamado.tipoSolicitacao;
        const status = chamado.status || 'Aberto';
        const statusClass = status === 'Aberto' ? 'status-aberto' : 'status-fechado';
        
        return `
            <tr>
                <td><strong>${chamado.numeroChamado}</strong></td>
                <td>${chamado.solicitante}</td>
                <td>${tipo}</td>
                <td>${dataFormatada}</td>
                <td><span class="badge ${statusClass}">${status}</span></td>
                <td>
                    <div class="acoes-chamado">
                       <button class="btn-ver" onclick="verChamado('${chamado.id}')">👁️ Ver</button>

${status === 'Aberto'
    ? `<button class="btn-fechar" onclick="fecharChamado('${chamado.id}')">✓ Fechar</button>`
    : ''
}

<button class="btn-deletar" onclick="deletarChamado('${chamado.id}')">🗑️ Deletar</button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function verChamado(id) {
    const chamado = chamadosAbertos[id];
    const tipo = tiposMap[chamado.tipoSolicitacao] || chamado.tipoSolicitacao;
    const detalhes = `
Número: ${chamado.numeroChamado}
Solicitante: ${chamado.solicitante}
Tipo: ${tipo}
Data de Abertura: ${new Date(chamado.dataAbertura).toLocaleString('pt-BR')}
Status: ${chamado.status || 'Aberto'}
Descrição: ${chamado.descricao}
    `;
    alert(detalhes);
}

function fecharChamado(id) {
    if (confirm('Deseja dar baixa neste chamado?')) {
        const chamado = chamadosAbertos.find(
    ch => ch.id === id
);
        chamado.status = 'Fechado';
        chamado.dataFechamento = new Date().toISOString();
        console.log('Chamado selecionado:', chamado);
        
        // Atualizar no servidor
        fetch(`${API_URL}?acao=atualizar`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(chamado)
        }).then(res => res.json()).then(data => {
           if (data.sucesso) {

    localStorage.setItem(
        'chamadosAbertos',
        JSON.stringify(chamadosAbertos)
    );

    sincronizarDados();

    alert('Chamado fechado com sucesso!');
}
        }).catch(erro => {
            console.error('Erro:', erro);
            alert('Erro ao fechar chamado!');
        });
    }
}

function deletarChamado(id) {
    if (confirm('Tem certeza que deseja deletar este chamado?')) {
        const chamado = chamadosAbertos.find( ch => ch.id === id );
        
        // Deletar do servidor
        fetch(`${API_URL}?acao=deletar`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: chamado.id })
        }).then(res => res.json()).then(data => {
            if (data.sucesso) {
                chamadosAbertos.splice(index, 1);
                localStorage.setItem('chamadosAbertos', JSON.stringify(chamadosAbertos));
                exibirChamados();
                alert('Chamado deletado com sucesso!');
            }
        }).catch(erro => {
            console.error('Erro:', erro);
            alert('Erro ao deletar chamado!');
        });
    }
}

function pesquisarChamados() {
    const pesquisa = document.getElementById('pesquisaChamado').value.toLowerCase();
    
    if (!pesquisa) {
        exibirChamados(chamadosAbertos);
        return;
    }
    
   const resultado = chamadosAbertos
    .filter(ch => ch.status !== 'Fechado')
    .filter(ch =>
        ch.numeroChamado.toLowerCase().includes(pesquisa) ||
        ch.solicitante.toLowerCase().includes(pesquisa)
    );  
    
    exibirChamados(resultado);
}

// Funções de Importação
function abrirImportacao() {
    const modal = document.getElementById('modalImportacao');
    modal.style.display = 'flex';
    document.getElementById('formImportacao').reset();
    document.getElementById('previewImportacao').style.display = 'none';
}

function fecharImportacao() {
    const modal = document.getElementById('modalImportacao');
    modal.style.display = 'none';
    document.getElementById('formImportacao').reset();
    document.getElementById('previewImportacao').style.display = 'none';
}

document.getElementById('arquivoImportacao').addEventListener('change', function(e) {
    const arquivo = e.target.files[0];
    if (!arquivo) return;
    
    const leitor = new FileReader();
    leitor.onload = function(evento) {
        try {
            let dados = [];
            const conteudo = evento.target.result;
            
            // Detectar tipo de arquivo
            if (arquivo.name.endsWith('.csv')) {
                dados = parseCSV(conteudo);
            } else if (arquivo.name.endsWith('.xlsx') || arquivo.name.endsWith('.xls')) {
                // Para XLSX/XLS, usar uma biblioteca simples ou avisar para usar CSV
                alert('Para arquivos Excel, favor converter para CSV antes de importar. Você pode fazer isso abrindo no Excel e salvando como "CSV (separado por vírgulas)"');
                return;
            }
            
            if (dados.length > 0) {
                mostrarPreview(dados);
            }
        } catch (erro) {
            alert('Erro ao ler arquivo: ' + erro.message);
        }
    };
    leitor.readAsText(arquivo);
});

function parseCSV(conteudo) {
    const linhas = conteudo.split('\n').filter(linha => linha.trim());
    const dados = [];
    
    // Pular cabeçalho (primeira linha)
    for (let i = 1; i < linhas.length; i++) {
        const valores = linhas[i].split(',').map(v => v.trim());
        
        if (valores.length >= 4) {
            const chamado = {
                numeroChamado: valores[0] || 'CHD-' + Date.now(),
                solicitante: valores[1],
                tipoSolicitacao: valores[2],
                dataAbertura: valores[3],
                descricao: valores[4] || '',
                status: 'Aberto',
                alertaMostrado: false,
                dataCriacao: new Date().toISOString()
            };
            
            // Validar tipo de solicitação
            if (!['pedido_aparelho', 'linha_nova', 'problema_linha'].includes(chamado.tipoSolicitacao)) {
                console.warn(`Tipo de solicitação inválido: ${chamado.tipoSolicitacao}`);
                continue;
            }
            
            dados.push(chamado);
        }
    }
    
    return dados;
}

function mostrarPreview(dados) {
    const previewDiv = document.getElementById('previewImportacao');
    const previewConteudo = document.getElementById('previewConteudo');
    
    let html = `<table style="width: 100%; font-size: 12px; border-collapse: collapse;">
        <tr style="background: #e2e8f0; font-weight: bold;">
            <td style="padding: 8px; border: 1px solid #cbd5e1;">Número</td>
            <td style="padding: 8px; border: 1px solid #cbd5e1;">Solicitante</td>
            <td style="padding: 8px; border: 1px solid #cbd5e1;">Tipo</td>
            <td style="padding: 8px; border: 1px solid #cbd5e1;">Data</td>
        </tr>`;
    
    dados.slice(0, 5).forEach(chamado => {
        html += `<tr>
            <td style="padding: 8px; border: 1px solid #cbd5e1;">${chamado.numeroChamado}</td>
            <td style="padding: 8px; border: 1px solid #cbd5e1;">${chamado.solicitante}</td>
            <td style="padding: 8px; border: 1px solid #cbd5e1;">${tiposMap[chamado.tipoSolicitacao] || chamado.tipoSolicitacao}</td>
            <td style="padding: 8px; border: 1px solid #cbd5e1;">${chamado.dataAbertura}</td>
        </tr>`;
    });
    
    if (dados.length > 5) {
        html += `<tr><td colspan="4" style="padding: 8px; text-align: center; background: #f1f5f9;">... e mais ${dados.length - 5} chamados</td></tr>`;
    }
    
    html += '</table>';
    previewConteudo.innerHTML = html;
    previewDiv.style.display = 'block';
    
    // Armazenar dados temporariamente
    window.dadosImportacao = dados;
}

document.getElementById('formImportacao').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!window.dadosImportacao || window.dadosImportacao.length === 0) {
        alert('Nenhum dado para importar. Por favor, selecione um arquivo válido.');
        return;
    }
    
    const confirmacao = confirm(`Deseja importar ${window.dadosImportacao.length} chamados?`);
    if (!confirmacao) return;
    
    try {
        // Importar no servidor
        fetch(`${API_URL}?acao=importar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(window.dadosImportacao)
        }).then(res => res.json()).then(data => {
            if (data.sucesso) {
                alert(`✅ ${data.total} chamados importados com sucesso!`);
                sincronizarDados();
                fecharImportacao();
                window.dadosImportacao = null;
            } else {
                alert('Erro ao importar: ' + (data.erro || 'Erro desconhecido'));
            }
        }).catch(erro => {
            console.error('Erro:', erro);
            alert('Erro ao importar chamados!');
        });
    } catch (erro) {
        alert('Erro ao importar chamados: ' + erro.message);
    }
});

// Verificar chamados com 24 horas
function verificarChamados24h() {
    const agora = new Date().getTime();
    chamadosAbertos.forEach((chamado, index) => {
        const dataAbertura = new Date(chamado.dataAbertura).getTime();
        const diferenca = agora - dataAbertura;
        const horas24 = 24 * 60 * 60 * 1000; // 24 horas em milissegundos
        
        if (diferenca >= horas24 && !chamado.alertaMostrado && chamado.status === 'Aberto') {
            // Marcar como alertado
            chamadosAbertos[index].alertaMostrado = true;
            
            // Mostrar notificação
            mostrarAlertaChamado24h(chamado);
            
            // Atualizar localStorage
            localStorage.setItem('chamadosAbertos', JSON.stringify(chamadosAbertos));
        }
    });
}

function mostrarAlertaChamado24h(chamado) {
    const alerta = document.createElement('div');
    alerta.className = 'alerta-24h';
    alerta.innerHTML = `
        <div class="alerta-conteudo">
            <div class="alerta-icone">⏰</div>
            <div class="alerta-texto">
                <h3>Chamado com 24 horas em aberto!</h3>
                <p><strong>Número:</strong> ${chamado.numeroChamado}</p>
                <p><strong>Solicitante:</strong> ${chamado.solicitante}</p>
                <p><strong>Aberto em:</strong> ${new Date(chamado.dataAbertura).toLocaleString('pt-BR')}</p>
            </div>
            <button class="alerta-fechar" onclick="this.parentElement.parentElement.remove()">✕</button>
        </div>
    `;
    
    document.body.appendChild(alerta);
    
    setTimeout(() => {
        if (alerta.parentElement) {
            alerta.remove();
        }
    }, 10000);
    
    notificarSonora();
}

function notificarSonora() {
    try {
        const contextoAudio = new (window.AudioContext || window.webkitAudioContext)();
        const oscilador = contextoAudio.createOscillator();
        const ganho = contextoAudio.createGain();
        
        oscilador.connect(ganho);
        ganho.connect(contextoAudio.destination);
        
        oscilador.frequency.value = 800;
        oscilador.type = 'sine';
        
        ganho.gain.setValueAtTime(0.3, contextoAudio.currentTime);
        ganho.gain.exponentialRampToValueAtTime(0.01, contextoAudio.currentTime + 0.5);
        
        oscilador.start(contextoAudio.currentTime);
        oscilador.stop(contextoAudio.currentTime + 0.5);
    } catch(e) {
        console.log('Áudio não disponível');
    }
}

// Submeter formulário
document.getElementById('formChamado').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const numeroChamado = document.getElementById('numeroChamado').value;
    const dataAbertura = document.getElementById('dataAbertura').value;
    const solicitante = document.getElementById('solicitante').value;
    const tipoSolicitacao = document.getElementById('tipoSolicitacao').value;
    const descricao = document.getElementById('descricao').value;
    
    const chamado = {
        numeroChamado,
        dataAbertura,
        solicitante,
        tipoSolicitacao,
        descricao,
        status: 'Aberto',
        alertaMostrado: false,
        dataCriacao: new Date().toISOString()
    };
    
    // Adicionar no servidor
    fetch(`${API_URL}?acao=adicionar`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(chamado)
    }).then(res => res.json()).then(data => {
        if (data.sucesso) {
            alert('Chamado ' + numeroChamado + ' criado com sucesso!');
            fecharFormularioChamado();
            sincronizarDados();
        } else {
            alert('Erro ao criar chamado!');
        }
    }).catch(erro => {
        console.error('Erro:', erro);
        alert('Erro ao criar chamado!');
    });
});

// Modal mostrar histórico
function mostrarHistorico() {

    const historico = chamadosAbertos.filter(
        chamado => chamado.status === 'Fechado'
    );

    exibirChamados(historico);
}

function mostrarAbertos() {

    const abertos = chamadosAbertos.filter(
        chamado => chamado.status !== 'Fechado'
    );

    exibirChamados(abertos);
}

// Verificar chamados a cada minuto
setInterval(verificarChamados24h, 60000);

// Sincronizar dados ao carregar a página
sincronizarDados();
</script>

</body>
</html>