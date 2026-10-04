<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centros de Custo</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

    <!-- MENU LATERAL -->

    <div class="sidebar">

        <div class="logo">
            📡 Gestão TI
        </div>

        <div class="menu">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="colaboradores.php">👥 Colaboradores</a>
            <a href="ativos.php">💻 Ativos</a>
            <a href="estoque.php">📦 Estoque</a>
            <a href="chamados.php">🎫 Chamados</a>

            <a href="centros_de_custo.php" class="active">
                🏢 Centros de Custo
            </a>

            <a href="relatorios.php">📈 Relatórios</a>
            <a href="configuracoes.php">⚙️ Configurações</a>
        </div>

    </div>

    <!-- CONTEÚDO -->

    <div class="content">

        <h1>Centros de Custo</h1>

        <div class="toolbar">

    <button class="btn" onclick="abrirModalCentroCusto()">
        ➕ Novo Centro
    </button>

    <button class="btn" onclick="abrirImportacao()">
        📥 Importar
    </button>

    <button class="btn" onclick="exportarCSV()">
        📤 Exportar
    </button>

    <button class="btn btn-copy" onclick="copiarTabelaCentros()">
        📋 Copiar página
    </button>

    <select id="filtroTipo">

        <option value="">
            📋 Todos
        </option>

        <option value="Loja">
            🏪 Lojas
        </option>

        <option value="CDS">
            🏢 CDS
        </option>

    </select>

    <input
        type="text"
        id="pesquisaCentro"
        placeholder="Pesquisar Centro de Custo, Filial ou Gerente...">

    <button class="btn" onclick="pesquisarCentros()">
        🔍 Pesquisar
    </button>

</div>

    <div class="floating-help" aria-label="Referência rápida de CNPJ e descrição">
        <button class="floating-help-trigger" type="button" aria-label="Abrir referência rápida">?</button>
        <div class="floating-help-content">
            <span class="floating-help-title">CNPJ / descrição</span>
            <ul class="floating-help-list" id="quickReferenceList"></ul>
        </div>
    </div>

        <div class="table-container">

            <table class="table-chamados">

                <thead>
                    <tr>
                        
                    <th>TIPO</th>
                    <th>CENTRO DE CUSTO</th>
                    <th>NOME / FILIAL</th>
                    <th>DESCRIÇÃO</th>
                    <th>TEL 1</th>
                    <th>TEL 2</th>
                    <th>AÇÕES</th>

                    </tr>
                </thead>

                <tbody id="tabelaCentros">

                    


                </tbody>

            </table>

        </div>

    </div>

    <!-- MODAL -->

    <div id="modalCentroCusto" class="modal">

        <div class="modal-content">

            <div class="modal-header">

                <h2>Novo Centro de Custo</h2>

                <span class="fechar"
                    onclick="fecharModalCentroCusto()">
                    &times;
                </span>

            </div>

            <form id="formCentroCusto" class="form-chamado">

            <div class="form-group">

    <label>Tipo</label>

    <select id="tipo">

        
        <option value="Loja">🏪 Loja</option>

        <option value="CDS">
            🏢 CDS
        </option>

    </select>

</div>

<div id="camposCDS" style="display:none;">

    <div class="form-group">
        <label>Colaborador</label>
        <input type="text" id="colaborador">
    </div>

    <div class="form-group">
        <label>Matrícula</label>
        <input type="text" id="matricula">
    </div>

    <div class="form-group">
        <label>CPF</label>
        <input type="text" id="cpf">
    </div>

    <div class="form-group">
        <label>Estabelecimento</label>
        <input type="text" id="estabelecimento">
    </div>

    <div class="form-group">
        <label>Descrição CC</label>
        <input type="text" id="descricaoCC">
    </div>

</div>

    <div class="form-group">
        <label>Nº Centro de Custo</label>
        <input type="text" id="numeroCentro" required>
    </div>

    <div class="form-group">
        <label>Bandeira</label>
        <input type="text" id="bandeira" required>
    </div>

    <div class="form-group">
        <label>Filial</label>
        <input type="text" id="filial" required>
    </div>

    <div class="form-group">
        <label>Gerente</label>
        <input type="text" id="gerente">
    </div>

    <div class="form-group">
        <label>Telefone 1</label>
        <input type="text" id="telefone1">
    </div>

    <div class="form-group">
        <label>Telefone 2</label>
        <input type="text" id="telefone2">
    </div>

        <div class="form-group">
    <label>CNPJ</label>
    <input type="text" id="cnpj">
</div>

<div class="form-group">
    <label>Endereço</label>
    <input type="text" id="endereco">
</div>

<div class="form-group">
    <label>Número</label>
    <input type="text" id="numero">
</div>

<div class="form-group">
    <label>Complemento</label>
    <input type="text" id="complemento">
</div>

<div class="form-group">
    <label>Bairro</label>
    <input type="text" id="bairro">
</div>

<div class="form-group">
    <label>Região</label>
    <input type="text" id="regiao">
</div>

<div class="form-group">
    <label>Cidade</label>
    <input type="text" id="cidade">
</div>

<div class="form-group">
    <label>UF</label>
    <input type="text" id="uf">
</div>

<div class="form-group">
    <label>CEP</label>
    <input type="text" id="cep">
</div>

<div class="form-group">
    <label>Perfil</label>
    <input type="text" id="perfil">
</div>

    <div class="form-actions">

        <button type="submit" class="btn-submit">
            Salvar
        </button>

        <button
            type="button"
            class="btn-cancel"
            onclick="fecharModalCentroCusto()">
            Cancelar
        </button>

    </div>

</form>

        </div>

    </div>
<div id="modalImportacao" class="modal">

    <div class="modal-content">

        <div class="modal-header">

            <h2>Importar Centros de Custo</h2>

            <span class="fechar"
                  onclick="fecharImportacao()">
                &times;
            </span>

        </div>
        
        <div class="form-group">


</div>  

        <div class="form-group">

    <label>Formato do arquivo</label>

    <p class="form-help">Para CDs, use CSV com as colunas Código CC, Colaborador, CPF, Estabelecimento e Descrição CC. Excel novo deve ser .xlsx.</p>

</div>
        <div class="form-group">
            <label>Arquivo CSV ou Excel</label>

            <input
                type="file"
                id="arquivoImportacao"
                accept=".csv,.xls,.xlsx">
        </div>

        <div class="form-actions">

            <button
                type="button"
                class="btn-submit"
                onclick="importarCentrosArquivo()">

                Importar

            </button>

            <button
                type="button"
                class="btn-cancel"
                onclick="fecharImportacao()">

                Cancelar

            </button>

        </div>

    </div>

</div>

   <script>

const API_URL = 'api_centros_de_custo.php';

let centros = [];
let centroEditando = null;

function normalizarContato(valor) {
   const texto = String(valor ?? '').trim();
   return texto;
}

function renderQuickReference(lista = centros) {
   const container = document.getElementById('quickReferenceList');
   if (!container) return;

   const itens = (lista || []).slice(0, 12).map((centro) => {
       const codigo = centro.numeroCentro || 'Sem código';
       const descricao = centro.descricaoCC || centro.bandeira || centro.filial || 'Sem descrição';
       const cnpj = centro.cnpj || 'CNPJ não informado';
       return `<li><strong>${codigo}</strong><br>${cnpj}<br>${descricao}</li>`;
   });

   container.innerHTML = itens.length ? itens.join('') : '<li>Nenhuma referência disponível.</li>';
}

function copiarTabelaCentros() {
   const tabela = document.getElementById('tabelaCentros');
   if (!tabela) {
       alert('Nenhuma tabela para copiar.');
       return;
   }

   const texto = tabela.innerText.trim();
   if (!texto) {
       alert('A tabela está vazia.');
       return;
   }

   const area = document.createElement('textarea');
   area.value = texto;
   area.setAttribute('readonly', '');
   area.style.position = 'fixed';
   area.style.top = '-9999px';
   document.body.appendChild(area);
   area.select();

   try {
       const ok = document.execCommand('copy');
       document.body.removeChild(area);
       alert(ok ? 'Dados da página copiados com sucesso.' : 'Não foi possível copiar automaticamente. Selecione o texto e copie manualmente.');
   } catch (erro) {
       document.body.removeChild(area);
       alert('Não foi possível copiar automaticamente. Selecione o texto e copie manualmente.');
   }
}

function abrirModalCentroCusto() {

    centroEditando = null;

    document.getElementById('formCentroCusto').reset();

    document.getElementById("modalCentroCusto").style.display = "flex";

}

function fecharModalCentroCusto() {

    document.getElementById("modalCentroCusto").style.display = "none";

    document.getElementById('formCentroCusto').reset();

    centroEditando = null;

}

window.onclick = function(event) {

    const modal = document.getElementById("modalCentroCusto");

    if (event.target === modal) {
        fecharModalCentroCusto();
    }
};

async function carregarCentros() {

    try {

        const resposta = await fetch(`${API_URL}?acao=listar`);

        centros = await resposta.json();

        exibirCentros();
        renderQuickReference(centros);

    } catch (erro) {

        console.error("Erro ao carregar centros:", erro);

    }

}

function exibirCentros() {

    const tabela = document.getElementById('tabelaCentros');

    if (!tabela) return;

    if (centros.length === 0) {

        tabela.innerHTML = `
            <tr>
                <td colspan="7" style="text-align:center;">
                    Nenhum centro cadastrado
                </td>
            </tr>
        `;

        renderQuickReference([]);
        return;
    }

    tabela.innerHTML = centros.map((centro, index) => {

    const nomeFilial =
        centro.tipo === 'CDS'
            ? (centro.colaborador || centro.filial || '')
            : (centro.filial || centro.colaborador || '');

   const descricao =
        centro.tipo === 'CDS'
            ? (centro.descricaoCC || centro.bandeira || '')
            : (centro.bandeira || centro.descricaoCC || '');

   const tel1 = normalizarContato(centro.telefone1);
   const tel2 = normalizarContato(centro.telefone2);

   return `

       <tr>

           <td>${centro.tipo || ''}</td>

           <td>
               <strong>
                   ${centro.numeroCentro || ''}
               </strong>
           </td>

           <td>${nomeFilial || ''}</td>

           <td>${descricao || ''}</td>

           <td>${tel1 || '-'}</td>

           <td>${tel2 || '-'}</td>

           <td>

                <div class="acoes-chamado">

                    <button class="btn-ver"
                        onclick="verCentro(${index})">
                        👁 Ver
                    </button>

                    <button class="btn-fechar"
                        onclick="editarCentro(${index})">
                        ✏ Editar
                    </button>

                    <button class="btn-deletar"
                        onclick="deletarCentro(${index})">
                        🗑 Excluir
                    </button>

                </div>

            </td>

        </tr>

    `;

}).join('');

renderQuickReference(centros);

}
function verCentro(index) {

    const centro = centros[index];
    const nome = centro.tipo === 'CDS'
        ? (centro.colaborador || '')
        : (centro.filial || '');

    const descricao = centro.tipo === 'CDS'
        ? (centro.descricaoCC || '')
        : (centro.bandeira || '');

    const tel1 = normalizarContato(centro.telefone1) || 'Não informado';
    const tel2 = normalizarContato(centro.telefone2) || 'Não informado';
    const localizacao = [centro.cidade, centro.uf].filter(Boolean).join(' / ') || 'Não informado';

    alert(
        "Centro de Custo: " + (centro.numeroCentro || '') +

        "\nNome / Filial: " + nome +

        "\nDescrição: " + descricao +

        "\nCPF: " + (centro.cpf || '') +

        "\nEstabelecimento: " + (centro.estabelecimento || '') +

        "\nLocalização: " + localizacao +

        "\nTelefone 1: " + tel1 +

        "\nTelefone 2: " + tel2
    );

}

function editarCentro(index) {
    centroEditando = centros[index];

    document.getElementById('tipo').value =
        centroEditando.tipo || 'Loja';

    document.getElementById('numeroCentro').value =
        centroEditando.numeroCentro || '';

    document.getElementById('bandeira').value =
        centroEditando.bandeira || '';

    document.getElementById('filial').value =
        centroEditando.filial || '';

    document.getElementById('gerente').value =
        centroEditando.gerente || '';

    document.getElementById('telefone1').value =
        centroEditando.telefone1 || '';

    document.getElementById('telefone2').value =
        centroEditando.telefone2 || '';

    document.getElementById('cnpj').value =
        centroEditando.cnpj || '';

    document.getElementById('endereco').value =
        centroEditando.endereco || '';

    document.getElementById('numero').value =
        centroEditando.numero || '';

    document.getElementById('complemento').value =
        centroEditando.complemento || '';

    document.getElementById('bairro').value =
        centroEditando.bairro || '';

    document.getElementById('regiao').value =
        centroEditando.regiao || '';

    document.getElementById('cidade').value =
        centroEditando.cidade || '';

    document.getElementById('uf').value =
        centroEditando.uf || '';

    document.getElementById('cep').value =
        centroEditando.cep || '';

    document.getElementById('perfil').value =
        centroEditando.perfil || '';

    document.getElementById('colaborador').value =
        centroEditando.colaborador || '';

    document.getElementById('matricula').value =
        centroEditando.matricula || '';

    document.getElementById('cpf').value =
        centroEditando.cpf || '';

    document.getElementById('estabelecimento').value =
        centroEditando.estabelecimento || '';

    document.getElementById('descricaoCC').value =
        centroEditando.descricaoCC || '';

    const cds = document.getElementById('camposCDS');
    cds.style.display = (centroEditando.tipo === 'CDS') ? 'block' : 'none';

    abrirModalCentroCusto();
}

function deletarCentro(index) {

    if (!confirm('Deseja realmente excluir este centro de custo?')) {
        return;
    }

    const centro = centros[index];

    fetch(`${API_URL}?acao=deletar`, {

        method: 'DELETE',

        headers: {
            'Content-Type': 'application/json'
        },

        body: JSON.stringify({
            id: centro.id
        })

    })

    .then(res => res.json())
    .then(data => {

        if (data.sucesso) {

            alert(data.mensagem);

            carregarCentros();

        } else {

            alert(data.erro || 'Erro ao excluir');

        }

    })

    .catch(erro => {

        console.error(erro);

        alert('Erro ao excluir');

    });

}

document.getElementById('formCentroCusto')
.addEventListener('submit', function(e) {

    e.preventDefault();

    

    const centro = {

    tipo:
        document.getElementById('tipo').value,

    numeroCentro:
        document.getElementById('numeroCentro').value,

    bandeira:
        document.getElementById('bandeira').value,

    filial:
        document.getElementById('filial').value,

    gerente:
        document.getElementById('gerente').value,

    telefone1:
        document.getElementById('telefone1').value,

    telefone2:
        document.getElementById('telefone2').value,

    colaborador:
        document.getElementById('colaborador')?.value || '',

    matricula:
        document.getElementById('matricula')?.value || '',

    cpf:
        document.getElementById('cpf')?.value || '',

    estabelecimento:
        document.getElementById('estabelecimento')?.value || '',

    descricaoCC:
        document.getElementById('descricaoCC')?.value || '',

    cnpj:
        document.getElementById('cnpj')?.value || '',

    endereco:
        document.getElementById('endereco')?.value || '',

    numero:
        document.getElementById('numero')?.value || '',

    complemento:
        document.getElementById('complemento')?.value || '',

    bairro:
        document.getElementById('bairro')?.value || '',

    regiao:
        document.getElementById('regiao')?.value || '',

    cidade:
        document.getElementById('cidade')?.value || '',

    uf:
        document.getElementById('uf')?.value || '',

    cep:
        document.getElementById('cep')?.value || '',

    perfil:
        document.getElementById('perfil')?.value || ''

};

    let url = `${API_URL}?acao=adicionar`;
    let metodo = 'POST';

    if (centroEditando) {

        centro.id = centroEditando.id;

        url = `${API_URL}?acao=atualizar`;
        metodo = 'PUT';
    }

    fetch(url, {

        method: metodo,

        headers: {
            'Content-Type': 'application/json'
        },

        body: JSON.stringify(centro)

    })

    .then(res => res.json())

    .then(data => {

        if (data.sucesso) {

            alert(data.mensagem);

            centroEditando = null;

            document.getElementById('formCentroCusto').reset();

            fecharModalCentroCusto();

            carregarCentros();

        } else {

            alert(data.erro || 'Erro');

        }

    })

   .catch(erro => {

        console.error(erro);

        alert('Erro ao salvar');

    });

});
function abrirImportacao() {

    document.getElementById(
        'modalImportacao'
    ).style.display = 'flex';

}

function fecharImportacao() {

    document.getElementById(
        'modalImportacao'
    ).style.display = 'none';

}

function importarCentrosArquivo() {

    const arquivo =
        document.getElementById('arquivoImportacao')
        .files[0];

    if (!arquivo) {

        alert('Selecione um arquivo CSV ou Excel.');

        return;
    }

    const formulario = new FormData();
    formulario.append('arquivo', arquivo);

    fetch(`${API_URL}?acao=importar`, {
        method: 'POST',
        body: formulario
    })
    .then(res => res.json())
    .then(data => {
        if (data.sucesso) {
            alert(data.total + ' centros importados com sucesso!');
            fecharImportacao();
            carregarCentros();
        } else {
            alert(data.erro || 'Erro na importação');
        }
    })
    .catch(() => alert('Não foi possível importar o arquivo.'));
}

function importarCSV() {

    const arquivo =
        document.getElementById('arquivoImportacao')
        .files[0];

    if (!arquivo) {
        alert('Selecione um arquivo CSV.');
        return;
    }

    const leitor = new FileReader();

    leitor.onload = function(e) {

        const linhas =
            e.target.result
            .split('\n')
            .filter(linha => linha.trim());

        const centrosImportados = [];

        for (let i = 1; i < linhas.length; i++) {

            const linha = linhas[i].trim();

            if (!linha) continue;

            const colunas = linha
                .split(';')
                .map(c => c.trim().replace(/^"|"$/g, ''));

            if (colunas.length < 2 || !colunas[0]) {
                continue;
            }

            centrosImportados.push({
                tipo: 'Loja',
                numeroCentro: colunas[0] || '',
                colaborador: colunas[1] || '',
                cpf: colunas[2] || '',
                estabelecimento: colunas[3] || '',
                descricaoCC: colunas[4] || ''
            });
        }

        fetch(`${API_URL}?acao=importar`, {

            method: 'POST',

            headers: {
                'Content-Type': 'application/json'
            },

            body: JSON.stringify(
                centrosImportados
            )

        })

        .then(res => res.json())

        .then(data => {

            if (data.sucesso) {

                alert(
                    data.total +
                    ' centros importados com sucesso!'
                );

                fecharImportacao();

                carregarCentros();

            } else {

                alert(
                    data.erro ||
                    'Erro na importação'
                );

            }

        });

    };

    leitor.readAsText(arquivo);

}
function pesquisarCentros() {

    const pesquisa =
        document.getElementById('pesquisaCentro')
        .value
        .toLowerCase()
        .trim();

    const tipo =
        document.getElementById('filtroTipo')
        .value;

    let resultado = [...centros];

    // filtro por tipo

    if (tipo) {

        resultado = resultado.filter(
            centro => centro.tipo === tipo
        );

    }

    // filtro por texto

    if (pesquisa) {

        resultado = resultado.filter(centro => {

            const tipo = (centro.tipo || '').toLowerCase();
            const numero = (centro.numeroCentro || '').toLowerCase();
            const bandeira = (centro.bandeira || '').toLowerCase();
            const filial = (centro.filial || '').toLowerCase();
            const gerente = (centro.gerente || '').toLowerCase();
            const colaborador = (centro.colaborador || '').toLowerCase();
            const descricao = (centro.descricaoCC || '').toLowerCase();
            const estabelecimento = (centro.estabelecimento || '').toLowerCase();
            const cpf = (centro.cpf || '').toLowerCase();

            return tipo.includes(pesquisa)
                || numero.includes(pesquisa)
                || bandeira.includes(pesquisa)
                || filial.includes(pesquisa)
                || gerente.includes(pesquisa)
                || colaborador.includes(pesquisa)
                || descricao.includes(pesquisa)
                || estabelecimento.includes(pesquisa)
                || cpf.includes(pesquisa);

        });

    }

    if (resultado.length === 0) {

        document.getElementById(
            'tabelaCentros'
        ).innerHTML = `
            <tr>
                <td colspan="7" style="text-align:center;">
                    Nenhum registro encontrado
                </td>
            </tr>
        `;

        renderQuickReference([]);
        return;
    }

    const tabela =
        document.getElementById('tabelaCentros');

    tabela.innerHTML = resultado.map((centro, index) => {

        const nomeFilial =
            centro.tipo === 'CDS'
                ? (centro.colaborador || centro.filial || '')
                : (centro.filial || centro.colaborador || '');

        const descricao =
            centro.tipo === 'CDS'
                ? (centro.descricaoCC || centro.bandeira || '')
                : (centro.bandeira || centro.descricaoCC || '');

            const tel1 = normalizarContato(centro.telefone1);
            const tel2 = normalizarContato(centro.telefone2);
            const indiceOriginal =
                centros.indexOf(centro);

            return `

                <tr>

                    <td>${centro.tipo || ''}</td>

                    <td>
                        <strong>
                            ${centro.numeroCentro || ''}
                        </strong>
                    </td>

                    <td>${nomeFilial || ''}</td>

                    <td>${descricao || ''}</td>

                    <td>${tel1 || '-'}</td>

                    <td>${tel2 || '-'}</td>

                <td>

                    <div class="acoes-chamado">

                        <button class="btn-ver"
                            onclick="verCentro(${indiceOriginal})">
                            👁 Ver
                        </button>

                        <button class="btn-fechar"
                            onclick="editarCentro(${indiceOriginal})">
                            ✏ Editar
                        </button>

                        <button class="btn-deletar"
                            onclick="deletarCentro(${indiceOriginal})">
                            🗑 Excluir
                        </button>

                    </div>

                </td>

            </tr>

        `;

    }).join('');

   renderQuickReference(resultado);

}

document
.getElementById('pesquisaCentro')
.addEventListener('keyup', function(event){

    if(event.key === 'Enter'){

        pesquisarCentros();

    }

});

    document.getElementById('tipo').addEventListener('change', function() {

    const cds =
        document.getElementById('camposCDS');

    if (this.value === 'CDS') {

        cds.style.display = 'block';

    } else {

        cds.style.display = 'none';

    }

});
carregarCentros();

    document
.getElementById('filtroTipo')
.addEventListener('change', pesquisarCentros);
</script>



</script>

</body>

</html>