<?php
require_once __DIR__ . '/auth.php';
exigirAutenticacao();
?>
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
        <a href="configuracoes.php">⚙️ Configurações</a>
    </div>
    

</div>

    

<div class="content">

<h1>Chamados</h1>

<div class="toolbar">
    <button onclick="abrirFormularioChamado()">Chamado</button>
        <button onclick="abrirImportacao()">📥 Importar</button>
        <button onclick="mostrarHistorico()">📚 Histórico</button>
            <button onclick="mostrarAbertos()">✅ Abertos</button>
        <button type="button" onclick="abrirTarefas()">Minhas tarefas</button>
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
                <textarea id="descricao" name="descricao" placeholder="Digite a descriÃ§Ã£o do chamado" rows="4"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Criar Chamado</button>
                <button type="button" class="btn-cancel" onclick="fecharFormularioChamado()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de tarefas pessoais -->
<div id="modalTarefas" class="modal">
    <div class="modal-content modal-tarefas-content">
        <div class="modal-header">
            <h2>Minhas tarefas</h2>
            <button type="button" class="fechar" aria-label="Fechar tarefas" onclick="fecharTarefas()">&times;</button>
        </div>
        <p class="tarefas-descricao">Anote pedidos recebidos e acompanhe o que ainda precisa de retorno.</p>
        <form id="formTarefa" class="form-tarefa">
            <label for="tituloTarefa">O que precisa ser feito?</label>
            <input type="text" id="tituloTarefa" maxlength="160" placeholder="Ex.: cobrar retorno do fornecedor" required>
            <label for="detalhesTarefa">Detalhes (opcional)</label>
            <textarea id="detalhesTarefa" rows="2" maxlength="500" placeholder="Pessoa, chamado ou próximo passo"></textarea>
            <button type="submit" class="btn-submit">Adicionar tarefa</button>
        </form>
        <div class="lembrete-controle">
            <label for="lembreteAtivo">Lembrar a cada 2 horas</label>
            <input type="checkbox" id="lembreteAtivo" onchange="alternarLembrete(this.checked)">
        </div>
        <p id="statusLembrete" class="status-lembrete" aria-live="polite"></p>
        <p id="statusPersistencia" class="status-lembrete" aria-live="polite">Carregando tarefas do servidor...</p>
        <div class="tarefas-secao">
            <h3>Pendentes <span id="totalPendentes">0</span></h3>
            <ul id="listaTarefasPendentes" class="lista-tarefas"></ul>
        </div>
        <div class="tarefas-secao tarefas-concluidas">
            <h3>Concluídas <span id="totalConcluidas">0</span></h3>
            <ul id="listaTarefasConcluidas" class="lista-tarefas"></ul>
        </div>
    </div>
</div>
<div id="avisoTarefas" class="aviso-tarefas" role="status" aria-live="assertive"></div>

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
                <label>Tipos de SolicitaÃ§Ã£o Aceitos:</label>
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
const API_URL = 'api_chamados.php';
const API_TAREFAS_URL = 'api_tarefas.php';
let chamadosAbertos = [];

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
}[char]));

function splitCsvLine(linha) {
    const campos = [];
    let valorAtual = '';
    let dentroAspas = false;

    for (let i = 0; i < linha.length; i++) {
        const caractere = linha[i];
        if (caractere === '"') {
            if (dentroAspas && linha[i + 1] === '"') {
                valorAtual += '"';
                i++;
            } else {
                dentroAspas = !dentroAspas;
            }
            continue;
        }

        if (caractere === ',' && !dentroAspas) {
            campos.push(valorAtual.trim());
            valorAtual = '';
            continue;
        }

        valorAtual += caractere;
    }

    campos.push(valorAtual.trim());
    return campos;
}

async function sincronizarDados() {
    try {
        const response = await fetch(`${API_URL}?acao=listar`);
        if (response.status === 401) {
            window.location.replace('login.php');
            return;
        }
        if (!response.ok) throw new Error('Resposta inválida do servidor');

        chamadosAbertos = await response.json();
        exibirChamados(chamadosAbertos.filter((chamado) => chamado.status !== 'Fechado'));
    } catch (erro) {
        console.error('Erro ao sincronizar chamados com o servidor:', erro);
        document.getElementById('tabelaChamados').innerHTML = '<tr><td colspan="6" style="text-align: center;">Não foi possível carregar os chamados do servidor. Verifique a conexão e tente novamente.</td></tr>';
    }
}

function abrirFormularioChamado() {
    const modal = document.getElementById('modalChamado');
    if (!modal) return;

    modal.style.display = 'flex';
    document.getElementById('numeroChamado').value = 'CHD-' + Date.now();

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
    if (modal) modal.style.display = 'none';
    const form = document.getElementById('formChamado');
    if (form) form.reset();
}

window.onclick = function (event) {
    const modal = document.getElementById('modalChamado');
    if (event.target === modal) {
        fecharFormularioChamado();
    }
    if (event.target === document.getElementById('modalTarefas')) {
        fecharTarefas();
    }
};

const CHAVE_TAREFAS = 'tarefasAcompanhamento';
const CHAVE_LEMBRETE_TAREFAS = 'lembreteTarefas';
const CHAVE_MIGRACAO_TAREFAS = 'tarefasAcompanhamentoMigradas';
const INTERVALO_LEMBRETE = 2 * 60 * 60 * 1000;
let tarefas = [];
let configuracaoLembrete = { ativo: false, proximoAlerta: null };
let tarefasCarregadas = false;
let carregamentoTarefas = null;
let filaSalvamentoTarefas = Promise.resolve();
let salvamentosPendentes = 0;

function lerJsonLocal(chave, padrao) {
    try {
        const valor = localStorage.getItem(chave);
        return valor ? JSON.parse(valor) : padrao;
    } catch (erro) {
        console.warn(`Não foi possível ler os dados locais de ${chave}:`, erro);
        return padrao;
    }
}

async function requisitarTarefas(url, opcoes = {}) {
    const resposta = await fetch(url, opcoes);
    if (resposta.status === 401) {
        window.location.replace('login.php');
        throw new Error('Sua sessão expirou. Entre novamente.');
    }

    const dados = await resposta.json();
    if (!resposta.ok) {
        throw new Error(dados.erro || 'Não foi possível acessar as tarefas no servidor.');
    }
    return dados;
}

async function carregarTarefasServidor() {
    if (carregamentoTarefas) return carregamentoTarefas;
    if (salvamentosPendentes) return;

    carregamentoTarefas = (async () => {
        const statusPersistencia = document.getElementById('statusPersistencia');
        if (!tarefasCarregadas) statusPersistencia.textContent = 'Carregando tarefas do servidor...';

        try {
            let estado = await requisitarTarefas(`${API_TAREFAS_URL}?acao=listar`);
            const tarefasLocais = lerJsonLocal(CHAVE_TAREFAS, []);
            const lembreteLocal = localStorage.getItem(CHAVE_LEMBRETE_TAREFAS);
            const haDadosLegados = (Array.isArray(tarefasLocais) && tarefasLocais.length > 0) || Boolean(lembreteLocal);
            const precisaMigrar = localStorage.getItem(CHAVE_MIGRACAO_TAREFAS) !== '1' || haDadosLegados;
            let totalTarefasMigradas = 0;

            if (precisaMigrar) {
                const dadosMigracao = {
                    tarefas: Array.isArray(tarefasLocais) ? tarefasLocais : []
                };
                totalTarefasMigradas = dadosMigracao.tarefas.length;
                if (lembreteLocal) {
                    dadosMigracao.lembrete = lerJsonLocal(CHAVE_LEMBRETE_TAREFAS, { ativo: false, proximoAlerta: null });
                }

                estado = await requisitarTarefas(`${API_TAREFAS_URL}?acao=migrar`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dadosMigracao)
                });
                localStorage.setItem(CHAVE_MIGRACAO_TAREFAS, '1');
                localStorage.removeItem(CHAVE_TAREFAS);
                localStorage.removeItem(CHAVE_LEMBRETE_TAREFAS);
            }

            tarefas = Array.isArray(estado.tarefas) ? estado.tarefas : [];
            configuracaoLembrete = estado.lembrete || { ativo: false, proximoAlerta: null };
            tarefasCarregadas = true;
            renderizarTarefas();
            statusPersistencia.textContent = totalTarefasMigradas > 0
                ? `${totalTarefasMigradas} tarefa(s) antigas importadas para o servidor.`
                : 'Tarefas carregadas do servidor; nenhuma tarefa antiga foi encontrada neste navegador.';
        } catch (erro) {
            console.error('Erro ao carregar tarefas do servidor:', erro);
            statusPersistencia.textContent = `${erro.message} As tarefas não foram alteradas.`;
            renderizarTarefas();
        } finally {
            carregamentoTarefas = null;
        }
    })();

    return carregamentoTarefas;
}

function salvarTarefas() {
    if (!tarefasCarregadas) return Promise.resolve();

    const corpo = JSON.stringify({ tarefas, lembrete: configuracaoLembrete });
    salvamentosPendentes++;
    document.getElementById('statusPersistencia').textContent = 'Salvando no servidor...';

    filaSalvamentoTarefas = filaSalvamentoTarefas.then(async () => {
        const estado = await requisitarTarefas(`${API_TAREFAS_URL}?acao=salvar`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: corpo
        });
        tarefas = estado.tarefas;
        configuracaoLembrete = estado.lembrete;
        renderizarTarefas();
        document.getElementById('statusPersistencia').textContent = 'Tarefas salvas no servidor.';
    }).catch((erro) => {
        console.error('Erro ao salvar tarefas no servidor:', erro);
        document.getElementById('statusPersistencia').textContent = `${erro.message} A alteração não foi salva.`;
    }).finally(() => {
        salvamentosPendentes--;
    });

    return filaSalvamentoTarefas;
}

async function abrirTarefas() {
    document.getElementById('modalTarefas').style.display = 'flex';
    document.getElementById('tituloTarefa').focus();
    await carregarTarefasServidor();
}

function fecharTarefas() {
    document.getElementById('modalTarefas').style.display = 'none';
}

function renderizarTarefas() {
    const pendentes = tarefas.filter((tarefa) => !tarefa.concluida);
    const concluidas = tarefas.filter((tarefa) => tarefa.concluida);
    document.getElementById('totalPendentes').textContent = pendentes.length;
    document.getElementById('totalConcluidas').textContent = concluidas.length;
    document.getElementById('listaTarefasPendentes').innerHTML = montarListaTarefas(pendentes, false);
    document.getElementById('listaTarefasConcluidas').innerHTML = montarListaTarefas(concluidas, true);
    document.getElementById('lembreteAtivo').checked = configuracaoLembrete.ativo;
    document.getElementById('lembreteAtivo').disabled = !tarefasCarregadas;
    document.querySelector('#formTarefa button[type="submit"]').disabled = !tarefasCarregadas;
    atualizarStatusLembrete();
}

function montarListaTarefas(lista, concluida) {
    if (!lista.length) return '<li class="tarefas-vazio">Nenhuma tarefa nesta lista.</li>';
    return lista.map((tarefa) => `
        <li class="tarefa-item ${concluida ? 'tarefa-concluida' : ''}">
            <div class="tarefa-texto">
                <strong>${escapeHtml(tarefa.titulo)}</strong>
                ${tarefa.detalhes ? `<p>${escapeHtml(tarefa.detalhes)}</p>` : ''}
            </div>
            <div class="tarefa-acoes">
                <button type="button" onclick="alternarTarefa('${tarefa.id}')" ${tarefasCarregadas ? '' : 'disabled'}>${concluida ? 'Reabrir' : 'Concluir'}</button>
                <button type="button" class="tarefa-remover" aria-label="Excluir tarefa" onclick="removerTarefa('${tarefa.id}')" ${tarefasCarregadas ? '' : 'disabled'}>Excluir</button>
            </div>
        </li>
    `).join('');
}

document.getElementById('formTarefa').addEventListener('submit', function (evento) {
    evento.preventDefault();
    if (!tarefasCarregadas) return;
    const titulo = document.getElementById('tituloTarefa').value.trim();
    if (!titulo) return;
    tarefas.unshift({
        id: (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()) + Math.random(),
        titulo,
        detalhes: document.getElementById('detalhesTarefa').value.trim(),
        concluida: false,
        criadaEm: new Date().toISOString()
    });
    this.reset();
    void salvarTarefas();
    document.getElementById('tituloTarefa').focus();
});

function alternarTarefa(id) {
    if (!tarefasCarregadas) return;
    const tarefa = tarefas.find((item) => item.id === id);
    if (!tarefa) return;
    tarefa.concluida = !tarefa.concluida;
    void salvarTarefas();
}

function removerTarefa(id) {
    if (!tarefasCarregadas) return;
    tarefas = tarefas.filter((tarefa) => tarefa.id !== id);
    void salvarTarefas();
}

async function alternarLembrete(ativo) {
    if (!tarefasCarregadas) return;
    if (ativo && 'Notification' in window && Notification.permission === 'default') {
        await Notification.requestPermission();
    }
    configuracaoLembrete = {
        ativo,
        proximoAlerta: ativo ? Date.now() + INTERVALO_LEMBRETE : null
    };
    atualizarStatusLembrete();
    await salvarTarefas();
}

function atualizarStatusLembrete() {
    const status = document.getElementById('statusLembrete');
    if (!configuracaoLembrete.ativo) {
        status.textContent = 'Lembrete desativado. Suas tarefas continuam salvas.';
        return;
    }
    status.textContent = 'Ativo: você será lembrada a cada 2 horas enquanto esta página estiver aberta.';
}

function verificarLembreteTarefas() {
    if (!configuracaoLembrete.ativo || !configuracaoLembrete.proximoAlerta || Date.now() < configuracaoLembrete.proximoAlerta) return;
    const pendentes = tarefas.filter((tarefa) => !tarefa.concluida);
    configuracaoLembrete.proximoAlerta = Date.now() + INTERVALO_LEMBRETE;
    void salvarTarefas();
    if (!pendentes.length) return;

    const mensagem = `Você tem ${pendentes.length} tarefa(s) pendente(s). Lembre-se de cobrar os retornos necessários.`;
    const aviso = document.getElementById('avisoTarefas');
    aviso.textContent = mensagem;
    aviso.classList.add('visivel');
    window.setTimeout(() => aviso.classList.remove('visivel'), 10000);
    if ('Notification' in window && Notification.permission === 'granted') {
        const notificacao = new Notification('Lembrete de tarefas', { body: mensagem });
        notificacao.onclick = () => {
            window.focus();
            abrirTarefas();
            notificacao.close();
        };
    }
}

void carregarTarefasServidor();
verificarLembreteTarefas();
setInterval(verificarLembreteTarefas, 30000);
setInterval(() => {
    if (document.visibilityState === 'visible') void carregarTarefasServidor();
}, 60000);

const tiposMap = {
    pedido_aparelho: '📱 Pedido de Aparelho',
    linha_nova: '📞 Solicitação de Linha Nova',
    problema_linha: '⚠️ Problema na Linha'
};

function exibirChamados(chamados = chamadosAbertos) {
    const tabelaChamados = document.getElementById('tabelaChamados');
    if (!tabelaChamados) return;

    if (chamados.length === 0) {
        tabelaChamados.innerHTML = '<tr><td colspan="6" style="text-align: center;">Nenhum chamado registrado</td></tr>';
        return;
    }

    tabelaChamados.innerHTML = chamados.map((chamado) => {
        const dataFormatada = new Date(chamado.dataAbertura || Date.now()).toLocaleString('pt-BR');
        const tipo = tiposMap[chamado.tipoSolicitacao] || chamado.tipoSolicitacao;
        const status = chamado.status || 'Aberto';
        const statusClass = status === 'Aberto' ? 'status-aberto' : 'status-fechado';
        const id = String(chamado.id || '').replace(/'/g, "\\'");

        return `
            <tr>
                <td><strong>${escapeHtml(chamado.numeroChamado || '')}</strong></td>
                <td>${escapeHtml(chamado.solicitante || '')}</td>
                <td>${escapeHtml(tipo || '')}</td>
                <td>${escapeHtml(dataFormatada)}</td>
                <td><span class="badge ${statusClass}">${escapeHtml(status)}</span></td>
                <td>
                    <div class="acoes-chamado">
                        <button class="btn-ver" onclick="verChamado('${id}')">👁️ Ver</button>
                        ${status === 'Aberto' ? `<button class="btn-fechar" onclick="fecharChamado('${id}')">✓ Fechar</button>` : ''}
                        <button class="btn-deletar" onclick="deletarChamado('${id}')">🗑️ Deletar</button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function verChamado(id) {
    const chamado = chamadosAbertos.find((item) => item.id === id);
    if (!chamado) return;

    const tipo = tiposMap[chamado.tipoSolicitacao] || chamado.tipoSolicitacao;
    const detalhes = [
        `Número: ${chamado.numeroChamado}`,
        `Solicitante: ${chamado.solicitante}`,
        `Tipo: ${tipo}`,
        `Data de Abertura: ${new Date(chamado.dataAbertura || Date.now()).toLocaleString('pt-BR')}`,
        `Status: ${chamado.status || 'Aberto'}`,
        `Descrição: ${chamado.descricao || ''}`
    ].join('\n');

    alert(detalhes);
}

function fecharChamado(id) {
    if (!confirm('Deseja dar baixa neste chamado?')) return;

    const chamado = chamadosAbertos.find((item) => item.id === id);
    if (!chamado) return;

    chamado.status = 'Fechado';
    chamado.dataFechamento = new Date().toISOString();

    fetch(`${API_URL}?acao=atualizar`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(chamado)
    }).then((res) => res.json()).then((data) => {
        if (data.sucesso) {
            sincronizarDados();
            alert('Chamado fechado com sucesso!');
        } else {
            alert('Erro ao fechar chamado: ' + (data.erro || 'Erro desconhecido'));
        }
    }).catch((erro) => {
        console.error('Erro:', erro);
        alert('Erro ao fechar chamado!');
    });
}

function deletarChamado(id) {
    if (!confirm('Tem certeza que deseja deletar este chamado?')) return;

    const chamado = chamadosAbertos.find((item) => item.id === id);
    if (!chamado) return;

    fetch(`${API_URL}?acao=deletar`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: chamado.id })
    }).then((res) => res.json()).then((data) => {
        if (data.sucesso) {
            const index = chamadosAbertos.findIndex((item) => item.id === id);
            if (index !== -1) chamadosAbertos.splice(index, 1);
            exibirChamados(chamadosAbertos.filter((item) => item.status !== 'Fechado'));
            alert('Chamado deletado com sucesso!');
        } else {
            alert('Erro ao deletar chamado: ' + (data.erro || 'Erro desconhecido'));
        }
    }).catch((erro) => {
        console.error('Erro:', erro);
        alert('Erro ao deletar chamado!');
    });
}

function pesquisarChamados() {
    const pesquisa = document.getElementById('pesquisaChamado').value.trim().toLowerCase();
    if (!pesquisa) {
        exibirChamados(chamadosAbertos.filter((ch) => ch.status !== 'Fechado'));
        return;
    }

    const resultado = chamadosAbertos
        .filter((ch) => ch.status !== 'Fechado')
        .filter((ch) => {
            const numero = String(ch.numeroChamado || '').toLowerCase();
            const solicitante = String(ch.solicitante || '').toLowerCase();
            return numero.includes(pesquisa) || solicitante.includes(pesquisa);
        });

    exibirChamados(resultado);
}

function abrirImportacao() {
    const modal = document.getElementById('modalImportacao');
    if (modal) modal.style.display = 'flex';
    const form = document.getElementById('formImportacao');
    if (form) form.reset();
    const preview = document.getElementById('previewImportacao');
    if (preview) preview.style.display = 'none';
}

function fecharImportacao() {
    const modal = document.getElementById('modalImportacao');
    if (modal) modal.style.display = 'none';
    const form = document.getElementById('formImportacao');
    if (form) form.reset();
    const preview = document.getElementById('previewImportacao');
    if (preview) preview.style.display = 'none';
}

document.getElementById('arquivoImportacao')?.addEventListener('change', function (e) {
    const arquivo = e.target.files[0];
    if (!arquivo) return;

    const leitor = new FileReader();
    leitor.onload = function (evento) {
        try {
            let dados = [];
            const conteudo = evento.target.result;

            if (arquivo.name.endsWith('.csv')) {
                dados = parseCSV(conteudo);
            } else if (arquivo.name.endsWith('.xlsx') || arquivo.name.endsWith('.xls')) {
                alert('Para arquivos Excel, favor converter para CSV antes de importar.');
                return;
            }

            if (dados.length > 0) mostrarPreview(dados);
        } catch (erro) {
            alert('Erro ao ler arquivo: ' + erro.message);
        }
    };
    leitor.readAsText(arquivo);
});

function parseCSV(conteudo) {
    const linhas = conteudo.split('\n').filter((linha) => linha.trim());
    const dados = [];

    for (let i = 1; i < linhas.length; i++) {
        const valores = splitCsvLine(linhas[i]);
        if (valores.length >= 4) {
            const chamado = {
                numeroChamado: valores[0] || 'CHD-' + Date.now(),
                solicitante: valores[1] || '',
                tipoSolicitacao: valores[2] || '',
                dataAbertura: valores[3] || '',
                descricao: valores[4] || '',
                status: 'Aberto',
                alertaMostrado: false,
                dataCriacao: new Date().toISOString()
            };

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

    let html = `<table style="width: 100%; font-size: 12px; border-collapse: collapse;">`;
    html += `<tr style="background: #e2e8f0; font-weight: bold;">`;
    html += `<td style="padding: 8px; border: 1px solid #cbd5e1;">Número</td>`;
    html += `<td style="padding: 8px; border: 1px solid #cbd5e1;">Solicitante</td>`;
    html += `<td style="padding: 8px; border: 1px solid #cbd5e1;">Tipo</td>`;
    html += `<td style="padding: 8px; border: 1px solid #cbd5e1;">Data</td>`;
    html += `</tr>`;

    dados.slice(0, 5).forEach((chamado) => {
        html += `<tr>`;
        html += `<td style="padding: 8px; border: 1px solid #cbd5e1;">${escapeHtml(chamado.numeroChamado || '')}</td>`;
        html += `<td style="padding: 8px; border: 1px solid #cbd5e1;">${escapeHtml(chamado.solicitante || '')}</td>`;
        html += `<td style="padding: 8px; border: 1px solid #cbd5e1;">${escapeHtml(tiposMap[chamado.tipoSolicitacao] || chamado.tipoSolicitacao || '')}</td>`;
        html += `<td style="padding: 8px; border: 1px solid #cbd5e1;">${escapeHtml(chamado.dataAbertura || '')}</td>`;
        html += `</tr>`;
    });

    if (dados.length > 5) {
        html += `<tr><td colspan="4" style="padding: 8px; text-align: center; background: #f1f5f9;">... e mais ${dados.length - 5} chamados</td></tr>`;
    }

    html += '</table>';
    previewConteudo.innerHTML = html;
    previewDiv.style.display = 'block';
    window.dadosImportacao = dados;
}

document.getElementById('formImportacao')?.addEventListener('submit', function (e) {
    e.preventDefault();

    if (!window.dadosImportacao || window.dadosImportacao.length === 0) {
        alert('Nenhum dado para importar. Por favor, selecione um arquivo válido.');
        return;
    }

    const confirmacao = confirm(`Deseja importar ${window.dadosImportacao.length} chamados?`);
    if (!confirmacao) return;

    fetch(`${API_URL}?acao=importar`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(window.dadosImportacao)
    }).then((res) => res.json()).then((data) => {
        if (data.sucesso) {
            alert(`✅ ${data.total} chamados importados com sucesso!`);
            sincronizarDados();
            fecharImportacao();
            window.dadosImportacao = null;
        } else {
            alert('Erro ao importar: ' + (data.erro || 'Erro desconhecido'));
        }
    }).catch((erro) => {
        console.error('Erro:', erro);
        alert('Erro ao importar chamados!');
    });
});

function verificarChamados24h() {
    const agora = new Date().getTime();
    chamadosAbertos.forEach((chamado, index) => {
        const dataAbertura = new Date(chamado.dataAbertura || Date.now()).getTime();
        const diferenca = agora - dataAbertura;
        const horas24 = 24 * 60 * 60 * 1000;

        if (diferenca >= horas24 && !chamado.alertaMostrado && chamado.status === 'Aberto') {
            chamadosAbertos[index].alertaMostrado = true;
            mostrarAlertaChamado24h(chamado);
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
                <p><strong>Número:</strong> ${escapeHtml(chamado.numeroChamado || '')}</p>
                <p><strong>Solicitante:</strong> ${escapeHtml(chamado.solicitante || '')}</p>
                <p><strong>Aberto em:</strong> ${escapeHtml(new Date(chamado.dataAbertura || Date.now()).toLocaleString('pt-BR'))}</p>
            </div>
            <button class="alerta-fechar" onclick="this.parentElement.parentElement.remove()">✕</button>
        </div>
    `;

    document.body.appendChild(alerta);
    setTimeout(() => {
        if (alerta.parentElement) alerta.remove();
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
    } catch (e) {
        console.log('Áudio não disponível');
    }
}

document.getElementById('formChamado')?.addEventListener('submit', function (e) {
    e.preventDefault();

    const numeroChamado = document.getElementById('numeroChamado').value.trim();
    const dataAbertura = document.getElementById('dataAbertura').value.trim();
    const solicitante = document.getElementById('solicitante').value.trim();
    const tipoSolicitacao = document.getElementById('tipoSolicitacao').value;
    const descricao = document.getElementById('descricao').value.trim();

    if (!numeroChamado || !solicitante || !tipoSolicitacao) {
        alert('Preencha número, solicitante e tipo de solicitação.');
        return;
    }

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

    fetch(`${API_URL}?acao=adicionar`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(chamado)
    }).then((res) => res.json()).then((data) => {
        if (data.sucesso) {
            alert('Chamado ' + numeroChamado + ' criado com sucesso!');
            fecharFormularioChamado();
            sincronizarDados();
        } else {
            alert('Erro ao criar chamado: ' + (data.erro || data.mensagem || 'erro desconhecido'));
        }
    }).catch((erro) => {
        console.error('Erro:', erro);
        alert('Erro ao criar chamado!');
    });
});

function mostrarHistorico() {
    const historico = chamadosAbertos.filter((chamado) => chamado.status === 'Fechado');
    exibirChamados(historico);
}

function mostrarAbertos() {
    const abertos = chamadosAbertos.filter((chamado) => chamado.status !== 'Fechado');
    exibirChamados(abertos);
}

setInterval(verificarChamados24h, 60000);
sincronizarDados();
</script>

</body>
</html>
