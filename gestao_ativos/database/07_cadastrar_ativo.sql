-- 07_cadastrar_ativo.sql
-- Use este script para inserir um novo ativo no estoque.
-- Altere os valores abaixo antes de executar.

INSERT INTO pecas_estoque (
    id_peca,
    tipo_peca,
    nome_peca,
    valor_unitario,
    qtd_inicial
) VALUES (
    1,
    'Hardware',
    'SSD 480GB Crucial',
    280.00,
    30
);

COMMIT;
