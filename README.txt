SGH - Sistema de Gestão de Horários

REQUISITOS
- XAMPP com Apache + MySQL/MariaDB
- PHP 8.2+

INSTALAÇÃO
1. Copie a pasta Feira7.0 para C:\xampp\htdocs\.
2. Abra phpMyAdmin e importe database/gestao_horarios.sql.
3. Aceda a http://localhost/Feira7.0/ (abre a landing page Horix; "Começar agora" leva ao login e, após entrar, ao painel).
4. Login inicial (Chefe de Departamento): admin@gestao.local / Admin@123
   Base de dados já existente? Importe antes database/migracao_perfis_utilizador.sql (converte os perfis antigos nos dois novos).

PERFIS E PERMISSÕES
- Chefe de Departamento: acesso total (utilizadores, formadores, módulos, salas, blocos, turmas, cursos, departamentos, geração, consulta e exportação PDF de horários).
- Formador: apenas Visão geral e consulta de Horários (só leitura; sem gerar, editar, eliminar nem exportar PDF).
- O perfil é lido da conta após o login e revalidado a cada pedido. O menu só mostra o que o perfil pode usar e o acesso por URL directo é bloqueado no servidor (bootstrap.php: page_acl()). Para alterar permissões, edite page_acl() e nav_all().
- Contas de exemplo: admin@gestao.local e coordenador@gestao.local = Chefe de Departamento; operador@gestao.local = Formador.
- O sistema impede remover ou despromover o último Chefe de Departamento activo.

FLUXO
Departamento -> Curso -> Bloco -> Sala -> Formador/Disponibilidade -> Modulo -> Turma -> Planeamento -> Gerar Horário.

GERAÇÃO
O GeradorHorarios.php usa backtracking. Cada aula candidata é testada contra conflitos de turma, formador e sala, capacidade, tipo de sala e disponibilidade. A gravação é transacional: em caso de falha, não fica um horário parcial.

PDF
O botão Exportar PDF (página Horários) abre o horário da turma numa folha A4 horizontal e abre o diálogo de impressão: escolha “Guardar como PDF”. O PDF inclui a grelha semanal e, por módulo: formador, verificador interno, carga horária e período (data de início e de fim).
- Verificador interno: outro formador que lecciona o mesmo módulo (associação em Formadores > Módulos) e que não o lecciona naquela turma. Se o módulo só tem um formador, aparece “—”.
- Período do módulo: calculado a partir da data de início da turma (ou da data em que o horário foi gerado) e da carga horária. Cada sessão de 4 tempos de 45 min conta 3 horas; o módulo termina na sessão em que a carga horária fica cumprida. Um novo horário da mesma turma começa no dia a seguir ao fim do anterior.
- Exportação geral: HorarioPDF.php (sem ?turma=) gera uma página por turma com horário.

NOTA DE SEGURANÇA
Depois da instalação, altere a palavra-passe da conta do Chefe de Departamento.
