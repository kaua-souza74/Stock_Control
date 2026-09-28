-- Atualiza os caminhos das fotos nos cinco produtos de exemplo.
-- Execute no banco stockcontrol já existente, sem apagar os demais dados.
USE `stockcontrol`;
START TRANSACTION;

UPDATE `produtos` SET `imagem` = 'imagens/brigadeiro.webp' WHERE `nome` = 'Brigadeiro';
UPDATE `produtos` SET `imagem` = 'imagens/pudim.webp' WHERE `nome` = 'Pudim de Leite Condensado';
UPDATE `produtos` SET `imagem` = 'imagens/pacoca.webp' WHERE `nome` = 'Paçoca';
UPDATE `produtos` SET `imagem` = 'imagens/cocada.webp' WHERE `nome` = 'Cocada';
UPDATE `produtos` SET `imagem` = 'imagens/goiabada.webp' WHERE `nome` = 'Goiabada';

COMMIT;
