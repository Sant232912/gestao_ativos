<?php
require_once __DIR__ . '/config.php';

/**
 * INTEGRAÇÃO COM GITHUB
 * Sincroniza automaticamente os dados com um repositório GitHub
 */

class GitHubSync {
    private $token;
    private $user;
    private $repo;
    private $branch;
    private $baseUrl;
    
    public function __construct() {
        $this->token = GITHUB_TOKEN;
        $this->user = GITHUB_USER;
        $this->repo = GITHUB_REPO;
        $this->branch = GITHUB_BRANCH;
        $this->baseUrl = "https://api.github.com/repos/{$this->user}/{$this->repo}";
    }
    
    /**
     * Sincroniza o arquivo de chamados com GitHub
     */
    public function sincronizar($mensagem = 'Atualização automática de chamados', $arquivoLocal = null, $caminhoGitHub = null) {
        if (!SYNC_GITHUB_ENABLED) {
            return ['sucesso' => false, 'erro' => 'Sincronização com GitHub desabilitada'];
        }

        $erros = verificarConfiguracao();
        if (!empty($erros)) {
            logSync('Erro: ' . implode(', ', $erros), 'error');
            return ['sucesso' => false, 'erro' => implode(', ', $erros)];
        }

        try {
            $arquivo = $arquivoLocal ?? CHAMADOS_FILE;
            $caminhoDestino = $caminhoGitHub ?? 'dados/chamados.json';

            if (!file_exists($arquivo)) {
                return ['sucesso' => false, 'erro' => 'Arquivo local não encontrado'];
            }

            $conteudo = file_get_contents($arquivo);

            $resultado = $this->obterSHA($caminhoDestino);
            logSync(
                "Arquivo enviado: " . $arquivo .
                " -> GitHub: " . $caminhoDestino,
                'info'
            );

            if ($resultado['sucesso']) {
                $sha = $resultado['sha'];
                return $this->atualizarArquivo(
                    $caminhoDestino,
                    $conteudo,
                    $sha,
                    $mensagem
                );
            }

            return ['sucesso' => false, 'erro' => $resultado['erro'] ?? 'Erro ao localizar o arquivo no GitHub'];
        } catch (Exception $e) {
            logSync('Erro ao sincronizar: ' . $e->getMessage(), 'error');
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }

    public function sincronizarArquivo($arquivoLocal, $caminhoGitHub, $mensagem = 'Atualização automática') {
        return $this->sincronizar($mensagem, $arquivoLocal, $caminhoGitHub);
    }
    
    /**
     * Obter SHA do arquivo no GitHub
     */
    private function obterSHA($caminho) {
        $url = "{$this->baseUrl}/contents/{$caminho}?ref={$this->branch}";
        
        $response = $this->fazerRequisicao('GET', $url);
        
        if ($response['codigo'] === 200) {
            $dados = json_decode($response['corpo'], true);
            return ['sucesso' => true, 'sha' => $dados['sha']];
        } else if ($response['codigo'] === 404) {
            return ['sucesso' => false, 'erro' => 'Arquivo não existe'];
        } else {
            return ['sucesso' => false, 'erro' => 'Erro ao obter SHA: ' . $response['corpo']];
        }
    }
    
    /**
     * Atualizar arquivo no GitHub
     */
    private function atualizarArquivo($caminho, $conteudo, $sha, $mensagem) {
        $url = "{$this->baseUrl}/contents/{$caminho}";
        
        $dados = [
            'message' => $mensagem,
            'content' => base64_encode($conteudo),
            'sha' => $sha,
            'branch' => $this->branch
        ];
        
        $response = $this->fazerRequisicao('PUT', $url, $dados);
        
        if ($response['codigo'] === 200) {
            logSync("Arquivo atualizado no GitHub: $caminho", 'info');
            return ['sucesso' => true, 'mensagem' => 'Arquivo sincronizado com sucesso'];
        } else {
            $erro = $response['corpo'];
            logSync("Erro ao atualizar arquivo: $erro", 'error');
            return ['sucesso' => false, 'erro' => $erro];
        }
    }
    
    /**
     * Criar novo arquivo no GitHub
     */
    private function criarArquivo($caminho, $conteudo, $mensagem) {
        $url = "{$this->baseUrl}/contents/{$caminho}";
        
        $dados = [
            'message' => $mensagem,
            'content' => base64_encode($conteudo),
            'branch' => $this->branch
        ];
        
        $response = $this->fazerRequisicao('PUT', $url, $dados);
        
        if ($response['codigo'] === 201) {
            logSync("Arquivo criado no GitHub: $caminho", 'info');
            return ['sucesso' => true, 'mensagem' => 'Arquivo criado no GitHub com sucesso'];
        } else {
            $erro = $response['corpo'];
            logSync("Erro ao criar arquivo: $erro", 'error');
            return ['sucesso' => false, 'erro' => $erro];
        }
    }
    
    /**
     * Fazer requisição à API do GitHub
     */
    private function fazerRequisicao($metodo, $url, $dados = null) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $metodo);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
            'Accept: application/vnd.github.v3+json',
            'Content-Type: application/json',
            'User-Agent: GestaoAtivos'
        ]);
        
        if ($dados) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
        }
        
        $resposta = curl_exec($ch);
        $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        return [
            'codigo' => $codigoHttp,
            'corpo' => $resposta
        ];
    }
    
    /**
     * Testar conexão com GitHub
     */
    public function testarConexao() {
        $url = "https://api.github.com/user";
        
        $response = $this->fazerRequisicao('GET', $url);
        
        if ($response['codigo'] === 200) {
            $dados = json_decode($response['corpo'], true);
            return [
                'sucesso' => true,
                'usuario' => $dados['login'],
                'repositorios' => $dados['public_repos']
            ];
        } else {
            return [
                'sucesso' => false,
                'erro' => 'Falha ao autenticar com GitHub. Verifique o token.'
            ];
        }
    }
    
    /**
     * Verificar se repositório existe
     */
    public function verificarRepositorio() {
        $url = "{$this->baseUrl}";
        
        $response = $this->fazerRequisicao('GET', $url);
        
        if ($response['codigo'] === 200) {
            $dados = json_decode($response['corpo'], true);
            return [
                'sucesso' => true,
                'nome' => $dados['name'],
                'url' => $dados['html_url'],
                'descricao' => $dados['description']
            ];
        } else {
            return [
                'sucesso' => false,
                'erro' => 'Repositório não encontrado'
            ];
        }
    }
}

// Função auxiliar para sincronizar
function sincronizarComGitHub($mensagem = null, $arquivoDestino = null) {
    if (!SYNC_GITHUB_ENABLED) {
        return false;
    }

    // Determinar o arquivo de forma explícita pelo chamador para evitar
    // erros de sincronização quando o texto da mensagem não identifica o tipo.
    $arquivo = CHAMADOS_FILE;
    $caminhoGitHub = 'dados/chamados.json';

    if ($arquivoDestino === 'centros' || $arquivoDestino === 'centros_de_custo' || stripos((string)$arquivoDestino, 'centro') !== false) {
        $arquivo = CENTROS_FILE;
        $caminhoGitHub = 'dados/centros_de_custo.json';
    } elseif ($arquivoDestino === 'chamados' || $arquivoDestino === 'chamado' || stripos((string)$arquivoDestino, 'chamado') !== false) {
        $arquivo = CHAMADOS_FILE;
        $caminhoGitHub = 'dados/chamados.json';
    } else {
        $textoMensagem = strtolower((string)($mensagem ?? ''));
        if (stripos($textoMensagem, 'centro') !== false) {
            $arquivo = CENTROS_FILE;
            $caminhoGitHub = 'dados/centros_de_custo.json';
        } elseif (stripos($textoMensagem, 'chamado') !== false) {
            $arquivo = CHAMADOS_FILE;
            $caminhoGitHub = 'dados/chamados.json';
        }
    }

    // Verificar intervalo mínimo entre sincronizações
    $ultimaSincronizacao = @filemtime(LOG_FILE);
    if ($ultimaSincronizacao && (time() - $ultimaSincronizacao) < SYNC_INTERVAL) {
        return false;
    }

    $sync = new GitHubSync();
    $resultado = $sync->sincronizarArquivo($arquivo, $caminhoGitHub, $mensagem ?? 'Atualização automática');

    return $resultado['sucesso'] ?? false;
}

?>
