# Plugin Relatório de Controle de Avaliadores

[English](README.md) · **Português (Brasil)** · [Español](README.es.md)

Este plugin disponibiliza uma página com um relatório de controle dos avaliadores do periódico, em *Estatísticas > Relatórios*.

## Compatibilidade

A versão mais recente deste plugin é compatível com as seguintes aplicações da PKP:

* OJS 3.4.x

## Instalação

1. Instale o plugin usando o Pacote mais recente disponível para a aplicação que você utiliza.

2. Envie o pacote e ative o plugin na página *Website > Plugins* do periódico.

## Utilização

Depois de ativar o plugin, uma nova página fica disponível em *Relatórios > Relatório de Controle de Avaliadores*.

A página lista os avaliadores do periódico na tela e gera dois relatórios em CSV:

* **Relatório por avaliadores** — uma linha por avaliador, com a média de notas,
  o total de avaliações completas e os títulos das submissões avaliadas.
* **Relatório por avaliações** — uma linha por avaliação concluída, com a
  submissão, a rodada de avaliação, as datas de designação, prazo e conclusão, a
  recomendação e a nota de qualidade, cada uma em sua própria coluna.

Os dois relatórios podem ser restringidos a um período, filtrado pela data de
conclusão do parecer. Deixar as duas datas em branco inclui todas as avaliações
concluídas. No relatório por avaliadores, a média de notas e o total de
avaliações consideram apenas o período escolhido, e os avaliadores sem nenhuma
avaliação nele continuam listados, com essas colunas vazias.

O arquivo é nomeado conforme o relatório e o período que ele cobre, por exemplo
`reviewsControlReport-20260101-20260331.csv`.

Somente as avaliações do periódico atual entram no relatório.

## Créditos
Este plugin foi patrocinado pelas revistas [Encontros Bibli](https://periodicos.ufsc.br/index.php/eb/), da Universidade Federal de Santa Catarina (UFSC), e [Revista Evidência](https://periodicos.unoesc.edu.br/evidencia), da Universidade do Oeste de Santa Catarina (Unoesc).

Desenvolvido por [Lepidus Tecnologia](https://github.com/lepidus).

## Licença

Este projeto é licenciado sob a GNU General Public License v3. Veja o arquivo [LICENSE](LICENSE) para os termos completos.
