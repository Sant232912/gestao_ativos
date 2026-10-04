CREATE VIEW v_relatorio_estoque AS
SELECT
    p.id_peca,
    p.nome_peca,
    p.qtd_inicial AS estoque_total,
    (
        SELECT COUNT(*)
        FROM chamados_suporte c
        WHERE c.id_peca = p.id_peca
    ) AS qtd_usada,
    (p.qtd_inicial -
        (
            SELECT COUNT(*)
            FROM chamados_suporte c
            WHERE c.id_peca = p.id_peca
        )
    ) AS saldo_atual
FROM pecas_estoque p;
