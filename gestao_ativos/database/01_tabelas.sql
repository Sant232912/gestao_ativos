-- 1. Tabela de Colaboradores
CREATE TABLE colaboradores (
    id_colab INTEGER PRIMARY KEY,
    nome_completo VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    departamento VARCHAR(50)
);

-- 2. Tabela de Estoque (Com as colunas de valor e tipo)
CREATE TABLE pecas_estoque (
    id_peca INTEGER PRIMARY KEY,
    nome_peca VARCHAR(100) UNIQUE,
    tipo_peca VARCHAR(50),
    valor_unitario DECIMAL(10,2),
    id_chip INTEGER,
    id_ICCID INTEGER,
    qtd_inicial INTEGER
);

-- 3. Tabela de Chamados (Relaciona colaboradores e peças)
CREATE TABLE chamados_suporte (
    id_chamado INTEGER PRIMARY KEY,
    id_colab INTEGER,
    id_peca INTEGER,
    descricao_problema VARCHAR(500),
    status_chamado VARCHAR(20),
    FOREIGN KEY (id_colab) REFERENCES colaboradores(id_colab),
    FOREIGN KEY (id_peca) REFERENCES pecas_estoque(id_peca)
);

-- 4. Tabela de Chips
CREATE TABLE chips (
    id_chip INTEGER PRIMARY KEY,
    id_colab INTEGER,
    id_ICCID INTEGER,
    FOREIGN KEY (id_colab) REFERENCES colaboradores(id_colab)
);
