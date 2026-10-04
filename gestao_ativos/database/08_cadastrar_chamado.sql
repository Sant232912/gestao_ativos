-- 08_cadastrar_chamado.sql
-- Use este script para inserir um novo chamado de suporte.
-- Altere os valores abaixo antes de executar.

INSERT INTO chamados_suporte (
    id_chamado,
    id_colab,
    id_peca,
    descricao_problema,
    status_chamado
) VALUES (
    1,
    1,
    1,
    'Computador não liga na inicialização.',
    'ABERTO'
);

COMMIT;
