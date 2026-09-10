# Módulo Informe de Control de Revisores

[English](README.md) · [Português (Brasil)](README.pt_BR.md) · **Español**

Este módulo ofrece una página con un informe de control de los revisores de la revista, en *Estadísticas > Informes*.

## Compatibilidad

La versión más reciente de este módulo es compatible con las siguientes aplicaciones de PKP:

* OJS 3.3.0

## Instalación

1. Instale el módulo usando el Paquete más reciente disponible para la aplicación que utiliza.

2. Suba el paquete y active el módulo en la página *Sitio web > Módulos* de la revista.

## Uso

Tras activar el módulo, queda disponible una nueva página en *Informes > Informe de Control del Revisor*.

La página lista en pantalla los revisores de la revista y genera dos informes en CSV:

* **Informe por revisores** — una fila por revisor, con la puntuación media, el
  total de revisiones completadas y los títulos de los envíos revisados.
* **Informe por revisiones** — una fila por revisión completada, con el envío,
  la ronda de revisión, las fechas de asignación, límite y finalización, la
  recomendación y la calificación de calidad, cada una en su propia columna.

Ambos informes pueden restringirse a un período, filtrado por la fecha de
finalización de la revisión. Dejar ambas fechas en blanco incluye todas las
revisiones completadas. En el informe por revisores, la puntuación media y el
total de revisiones abarcan solo el período elegido, y los revisores sin
ninguna revisión en él siguen apareciendo, con esas columnas vacías.

El archivo se nombra según el informe y el período que abarca, por ejemplo
`reviewsControlReport-20260101-20260331.csv`.

Solo se informan las revisiones de la revista actual.

## Créditos
Este módulo fue patrocinado por las revistas [Encontros Bibli](https://periodicos.ufsc.br/index.php/eb/), de la Universidade Federal de Santa Catarina (UFSC), y [Revista Evidência](https://periodicos.unoesc.edu.br/evidencia), de la Universidade do Oeste de Santa Catarina (Unoesc).

Desarrollado por [Lepidus Tecnologia](https://github.com/lepidus).

## Licencia

Este proyecto está licenciado bajo la GNU General Public License v3. Consulte el archivo [LICENSE](LICENSE) para conocer los términos completos.
