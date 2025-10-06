DELETE FROM Respostas;
DELETE FROM Tokens;
DELETE FROM Participantes;

INSERT INTO Participantes (nome, foto_adulto, foto_crianca, codigo_unico, ja_jogou) VALUES
('Ana Souza', '/fotos/adulto_ana.jpg', '/fotos/crianca_ana.jpg', 'ANA-123', 0),
('Bruno Lima', '/fotos/adulto_bruno.jpg', '/fotos/crianca_bruno.jpg', 'BRU-456', 0),
('Carla Dias', '/fotos/adulto_carla.jpg', '/fotos/crianca_carla.jpg', 'CAR-789', 0),
('Diego Nunes', '/fotos/adulto_diego.jpg', '/fotos/crianca_diego.jpg', 'DIE-321', 0),
('Elisa Prado', '/fotos/adulto_elisa.jpg', '/fotos/crianca_elisa.jpg', 'ELI-654', 0);
