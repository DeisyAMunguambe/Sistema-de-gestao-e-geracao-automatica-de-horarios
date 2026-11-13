GERAÇÃO DE HORÁRIOS — FEIRA7.0

Fluxo sem Planeamento:
1. Cadastre cursos, módulos, turmas e salas. Os módulos do curso da turma serão considerados automaticamente.
2. Em Formadores, use o botão Disponibilidade para registar um ou vários intervalos por dia.
3. Em Formadores, use o botão Módulos para associar um ou vários módulos ao formador. Um módulo pode ter vários formadores elegíveis.
4. Abra Horários e clique em Gerar com backtracking.

Regras implementadas:
- Cada módulo do curso de cada turma recebe duas sessões por semana.
- As duas sessões do mesmo módulo são colocadas em dias não consecutivos.
- Cada sessão ocupa quatro tempos consecutivos de 45 minutos (mínimo pedido).
- O turno da manhã começa às 07:00 e o da noite às 17:00. Ao sábado, a janela de geração é 07:00–14:00; o sábado só é usado quando não existe uma solução válida de segunda a sexta.
- São verificados conflitos de turma, formador e sala, capacidade, tipo de sala e disponibilidade do formador.
- A associação módulo-formador é gerida em Formadores > Módulos, sem depender da página/tabela de Planeamento.
- Após gerar, a página apresenta a duração prevista apenas dos módulos incluídos no horário seleccionado. É possível criar vários horários para a mesma turma; os módulos dos horários anteriores são excluídos da geração seguinte.

Base de dados:
Usa o schema original database/gestao_horarios.sql. A tabela for_mod já suporta muitos-para-muitos entre formadores e módulos, e disponibilidade suporta vários intervalos por formador. A tabela turma_modulo pode continuar no schema antigo, mas o gerador já não a consulta.

CORREÇÃO (13/10/2026) — geração por lotes:
Uma turma não consegue ter todos os módulos do curso na mesma semana (só cabe 1 sessão de 4 tempos por dia).
O gerador agenda agora só os primeiros módulos (por ordem) que cabem (máx. 5 sessões de segunda a sexta;
só recorre ao sábado, até 7 sessões, se nem 1 módulo couber). Os restantes módulos são agendados nos
cliques seguintes em "Gerar novo horário". Limite de 20000 tentativas e de 20 s por pedido.


CHAT BOX FLUTUANTE
- Na tela GerarHorario.php, use o ícone flutuante no canto inferior direito para abrir o Chat Box.
- O chat permite conversa entre formadores e Chefe de Departamento e associação de mensagens a aulas do formador.
- Base de dados nova: as tabelas do chat estão incluídas em database/gestao_horarios.sql.
- Base de dados já existente: importe database/migracao_chat.sql na base gestao_horarios.
- Para o formador, o e-mail do utilizador deve coincidir com o e-mail registado em Formador.
