# Sistema de Sincronização de Chamados

## Como Funciona

O sistema de chamados agora sincroniza dados com o servidor, permitindo que os chamados sejam acessados de qualquer navegador (Chrome, Firefox, Explorer, Safari, etc).

### Arquitetura

1. **Frontend** (chamados.php):
   - Interface de usuário
   - Sincroniza com o servidor via API
   - Mantém cópia local no localStorage como fallback

2. **Backend** (api_chamados.php):
   - API REST que gerencia chamados
   - Salva dados em arquivo JSON (dados/chamados.json)
   - Operações: Listar, Adicionar, Atualizar, Deletar, Importar

3. **Storage** (dados/chamados.json):
   - Arquivo JSON que armazena todos os chamados
   - Sincronizado com servidor

## Funcionalidades

✅ **Sincronização entre navegadores**
- Chrome, Firefox, Explorer, Safari, etc
- Acesse de qualquer computador na rede

✅ **Operações Disponíveis**
- Criar chamados
- Importar em massa (CSV)
- Pesquisar
- Fechar/Dar baixa
- Deletar
- Visualizar detalhes

✅ **Persistência**
- Dados salvos no servidor
- Histórico completo

## Endpoints da API

- GET `api_chamados.php?acao=listar` - Listar todos os chamados
- POST `api_chamados.php?acao=adicionar` - Criar novo chamado
- POST `api_chamados.php?acao=importar` - Importar múltiplos chamados
- PUT `api_chamados.php?acao=atualizar` - Atualizar chamado
- DELETE `api_chamados.php?acao=deletar` - Deletar chamado

## Como Acessar

1. No Chrome/Firefox/Explorer/Safari:
   - Acesse: `http://localhost/gestao_ativos/chamados.php`
   - Os dados são sincronizados automaticamente com o servidor

2. Os dados são salvos em `dados/chamados.json` no servidor

## Estrutura de um Chamado

```json
{
  "id": "unique_id",
  "numeroChamado": "CHD-123456",
  "solicitante": "João Silva",
  "tipoSolicitacao": "pedido_aparelho",
  "dataAbertura": "2024-01-15T10:30",
  "descricao": "Pedido de notebook",
  "status": "Aberto",
  "alertaMostrado": false,
  "dataCriacao": "2024-01-15T10:30:00.000Z"
}
```

## Troubleshooting

Se não sincronizar:
1. Verifique se a pasta `dados/` tem permissões de escrita
2. Verifique se o arquivo `dados/chamados.json` existe
3. Verifique o console do navegador (F12) para mensagens de erro
4. O localStorage local será usado como fallback
