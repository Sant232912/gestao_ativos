-- Limpa chamados anteriores
DELETE FROM chamados_suporte;

-- Insere 80 chamados com motivos reais em SQL padrão.
-- Use um gerador de séries se sua base suportar CTEs recursivas.
WITH RECURSIVE numeros AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM numeros WHERE n < 80
)
INSERT INTO chamados_suporte (id_chamado, id_colab, id_peca, descricao_problema, status_chamado)
SELECT
    n,
    n,
    CASE
        WHEN MOD(n, 3) = 0 THEN 1
        WHEN MOD(n, 3) = 1 THEN 2
        ELSE 3
    END,
    CASE
        WHEN MOD(n, 3) = 0 THEN 'Lentidão no sistema e travamento ao abrir o navegador.'
        WHEN MOD(n, 3) = 1 THEN 'Monitor piscando ou com cores distorcidas após algum tempo.'
        ELSE 'Computador não liga ou trava na tela de carregamento.'
    END,
    CASE
        WHEN MOD(n, 3) = 0 THEN 'Aberto'
        WHEN MOD(n, 3) = 1 THEN 'Em Atendimento'
        ELSE 'Concluido'
    END
FROM numeros;
COMMIT;
