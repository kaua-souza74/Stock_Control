-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 28/09/2026 às 14:06
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `stockcontrol`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `comentarios`
--

CREATE TABLE `comentarios` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `texto` text NOT NULL,
  `data_comentario` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `comentarios`
--

INSERT INTO `comentarios` (`id`, `produto_id`, `usuario_id`, `texto`, `data_comentario`) VALUES
(1, 1, 1, 'Produto com alta rotatividade durante as tardes.', '2026-09-28 12:06:18'),
(2, 1, 2, 'Manter refrigerado antes de colocar na vitrine.', '2026-09-28 12:06:18'),
(3, 2, 1, 'Verificar data de validade devido aos laticínios.', '2026-09-28 12:06:18'),
(4, 3, 2, 'Lote com embalagens individuais intactas.', '2026-09-28 12:06:18'),
(5, 5, 1, 'Ótima combinação para vender junto com queijo minas.', '2026-09-28 12:06:18');

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacoes`
--

CREATE TABLE `movimentacoes` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('entrada','saida') NOT NULL,
  `quantidade` int(11) NOT NULL,
  `data_movimentacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `movimentacoes`
--

INSERT INTO `movimentacoes` (`id`, `produto_id`, `usuario_id`, `tipo`, `quantidade`, `data_movimentacao`, `observacoes`) VALUES
(1, 1, 1, 'entrada', 60, '2026-09-28 12:06:18', 'Lote inicial de brigadeiros recebido do fornecedor.'),
(2, 1, 2, 'saida', 10, '2026-09-28 12:06:18', 'Venda para evento interno.'),
(3, 2, 1, 'entrada', 15, '2026-09-28 12:06:18', 'Produção diária de pudins.'),
(4, 3, 1, 'entrada', 100, '2026-09-28 12:06:18', 'Compra de caixa de paçocas.'),
(5, 4, 1, 'entrada', 40, '2026-09-28 12:06:18', 'Reabastecimento de estoque de cocadas.'),
(6, 5, 1, 'entrada', 30, '2026-09-28 12:06:18', 'Recebimento de goiabadas em barra.');

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` text NOT NULL,
  `quantidade` int(11) NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `imagem` varchar(255) NOT NULL,
  `data_inclusao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `nome`, `descricao`, `quantidade`, `preco`, `status`, `imagem`, `data_inclusao`) VALUES
(1, 'Brigadeiro', 'Brigadeiro tradicional de chocolate feito com leite condensado e granulado.', 50, 2.50, 1, 'imagens/brigadeiro.webp', '2026-09-28 12:06:18'),
(2, 'Pudim de Leite Condensado', 'Pudim caseiro com calda de caramelo e textura cremosa.', 15, 12.00, 1, 'imagens/pudim.webp', '2026-09-28 12:06:18'),
(3, 'Paçoca', 'Paçoca de amendoim tradicional, formato rolha.', 100, 1.50, 1, 'imagens/pacoca.webp', '2026-09-28 12:06:18'),
(4, 'Cocada', 'Cocada cremosa artesanal feita com coco ralado.', 40, 3.00, 1, 'imagens/cocada.webp', '2026-09-28 12:06:18'),
(5, 'Goiabada', 'Goiabada cascão em pedaço, ideal para sobremesas.', 30, 5.00, 1, 'imagens/goiabada.webp', '2026-09-28 12:06:18');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `is_admin`) VALUES
(1, 'Administrador', 'admin@stockcontrol.com', 'admin123', 1),
(2, 'Carlos Silva', 'carlos@stockcontrol.com', 'user123', 0);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `comentarios`
--
ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produto_id` (`produto_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `movimentacoes`
--
ALTER TABLE `movimentacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produto_id` (`produto_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `movimentacoes`
--
ALTER TABLE `movimentacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `movimentacoes`
--
ALTER TABLE `movimentacoes`
  ADD CONSTRAINT `movimentacoes_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `movimentacoes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
