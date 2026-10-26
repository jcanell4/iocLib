/**
 * Aquest plugin permet desplaçar elements a la columna B, alineant-los a dreta o esquerra segons la configuració.
 * L'alineació lateral només es fiable perls continguts centrals de ipus paràgraf i llistes.
 *
 * En el cas de dispositius mòbils es mostrarà només una icona per mostrar i ocultar-lo.
 *
 * Es pot configurar mitjançant un objecte de configuració amb les següents opcions:
 *     forceIcons: true, // true | false. Si és true mostrarà sempre la icona desplegable i amagarà la columna b
 *     columnAlign: 'right', // left | right
 *     beforeContainer: 'p, ul, ol', // selectors per col·locar l'element abans per amplada d'escriptori
 *     defaultIcon: '../../../img/iocinclude.png', // Icona per defecte per desplegar els elements en
 *                                                 // amplada mòbil. Si no s'especifica s'intenta utilitzar el fons
 *                                                 // del contenidor de la columna (la nota, el text, etc.)
 *     icon: false  // Utilitza aquesta URL com a icon, pot ser una simple url o un objecte on s'assigna una
 *                  // icona a cada classe css (la classe de l'element que va a la columna b, per exemple
 *                  // {"iocreference" : "../../../img/iocreference.png)}
 *     lateralMargin: '2em', // Marge lateral de la columna
 *     debug: false, // Si és cert mostra un requadre vermell o verd segons si s'ha desplaçat el bloc
 *                               // de contingut (la nota, la imatge lateral, el text, etc.) o no.
 *     forceClear: true, // força el reemplaçament de la propietat clear, accepta una query com a valor
 *     class: undefined, // afegeix aquesta classe a l'element de la columna b,
 *     maxTitleLength: 60 // mida màxima dels tìtols que es mostren en fer mouseover sobre les icones (replegades)
 *
 * @example
 *     $(".iocnote, .iocreference, .ioctext, .iocfigurec").toBColumn({debug: true, columnAlign: 'left', forceClear: true});
 *     $(".iocnote, .iocreference, .ioctext, .iocfigurec").toBColumn({debug: true, columnAlign: 'left', forceClear: {query: 'p'}});
 *     $(".iocnote, .iocreference, .ioctext, .iocfigurec").toBColumn({minWidth: 0});
 *     jQuery(".iocfigurec").toBColumn();
 *
 * @known_issues
 *     - No tots els elements permeten l'alinació de columnes, encara que s'apliqui la opció forceClear, per exemple
 *       les figures principals
 *     - Per forçar la inclusió del CSS ho afegim com un node style directament al final del document, això fa que
 *     - sempre sobrescriguin regles anteriors.
 *
 *
 *
 * @author Xavier Garcia <xaviergaro.dev@gmail.com>
 */
(function ($) {

    // Afegim el node style
//    var css =
//        ".column-b {\n" +
//        "    margin-bottom: 1em;\n" +
//        "}\n" +
//        "\n" +
//        ".column-b.right {\n" +
//        "    margin-left: 2em;\n" +
//        "    float: right;\n" +
//        "    clear: right;\n" +
//        "}\n" +
//        "\n" +
//        ".column-b.left {\n" +
//        "    float: left;\n" +
//        "    clear: left;\n" +
//        "    margin-right: 2em;\n" +
//        "}\n" +
//        "\n" +
//        ".column-b {\n" +
//        "    display:none;\n" +
//        "}\n" +
//        "\n" +
//        ".column-b.mobile {\n" +
//        "    float: none;" +
//        "    margin-left: 0;" +
//        "    margin-right: 0;" +
//        "    display:block;\n" +
//        "}\n" +
//        "\n" +
//        "img.right {\n" +
//        "    float: right;\n" +
//        "}\n" +
//        "\n" +
//        ".column-b.right.mobile, .column-b.left.mobile {\n" +
//        "    clear: inherit;\n" +
//        "}" +
//        "img.left {\n" +
//        "    float: left;\n" +
//        "}\n" +
//        ".debug {\n" +
//        "    background-color: #d4edda;\n" +
//        "    border: 1px solid #c3e6cb;\n" +
//        "}\n" +
//        "\n" +
//        "@media (min-width: 992px) {\n" +
//        "    .column-b {\n" +
//        "        display:block;\n" +
//        "    }\n" +
//        "\n" +
//        "    .column-b.mobile {\n" +
//        "        display:none;\n" +
//        "    }\n" +
//        "}" +
//        ".column-b .hide {\n" +
//        "    display: none\n" +
//        "}\n" +
//        ".column-b .iocfigurec {\n" + // això és un arreglo forçat per la imatge lateral, queda massa petita
//        "    max-width:inherit;\n" +
//        "}";
//
//
//     //Ho desactivem, estem provant a integrar el fitxer css amb la build
//     var $style = jQuery('<style>');
//     $style.text(css);
//     jQuery('html').append($style);

    $.fn.toBColumn = function (options) {
        var settings = $.extend({
            forceIcons: false, // si mostrarà sempre les icones, ocultant els blocs
            columnAlign: 'right', // left | right
            beforeContainer: 'p, ul, ol', // selectors per col·locar l'element abans per amplada d'escriptori
            icon: false, // si s'assigna un icon es farà servir aquest, en lloc d'intentar cercar-lo
            defaultIcon: 'img/iocinclude.png', // Icona per defecte per desplegar els elements en amplada mòbil
            lateralMargin: '2em', // Marge lateral de la columna
            debug: false, // Si és cert mostra un requadre vermell o verd segons si s'ha desplaçat el bloc
                          // de contingut (la nota, la imatge lateral, el text, etc.) o no.
            forceClear: true, // força el reemplaçament de la propietat clear, accepta una query com a valor
            class: undefined, // classe CSS per aplicar a la columna
            maxTitleLength: 60 // mida màxima dels tìtols que es mostren sobre les icones

        }, options);


        this.each(function () {


            // PROVA: creem dos nous divs, amb classe column-b i column-b mobile.
            var $columnb = jQuery('<div class="column-b"></div>');
            $columnb.addClass(settings.columnAlign);


            var $content = jQuery(this);

            // $this.css('float', settings.columnAlign);
            // $this.css('clear', settings.columnAlign);

            // if (settings.columnAlign === 'right') {
            //     $content.css('margin-left', settings.lateralMargin);
            //     $content.css('margin-right', 0);
            // } else {
            //     $content.css('margin-left', 0);
            //     $content.css('margin-right', settings.lateralMargin);
            // }

            if (settings.class) {
                // $content.addClass(settings.class);
                $columnb.addClass(settings.class);
            }

            var $targetBlock = $content.prevAll(settings.beforeContainer).first();

            // Hem de cercar el targetblock previ adequat
            while ($targetBlock.length > 0 && $targetBlock.children().length === 0 && $targetBlock.text().trim().length === 0) {
                $targetBlock = $targetBlock.prevAll(settings.beforeContainer).first();
            }

            // Si no s'ha trobat no cal moure el content
            if ($targetBlock.length > 0) {
                $targetBlock.css('clear', 'inherit');
                $content.insertBefore($targetBlock);

                // Això és necessari perquè segons el css (això passa amb el del iocexport) pot ser que els
                // paràgrafs del bloc facin un clear de les columnes, llavors no s'alinea la columna b pels subsegüents paràgrafs
                if (settings.forceClear === true) {
                    $targetBlock.siblings('p').css('clear', 'inherit');
                } else if (settings.forceClear) {
                    $targetBlock.siblings(settings.forceClear.query).css('clear', 'inherit');
                }
            }


            if (settings.debug === true) {
                $targetBlock.addClass('debug');
                // TODO: Afegir classe 'debug'
                // No hi ha previ, no cal recol·locar
                // $target.css('background-color', '#d4edda');
                // $target.css('border', '1px solid #c3e6cb');
            }


            $columnb.insertBefore($content);


            // if (settings.minWidth > 0 && window.matchMedia('(min-width: ' + settings.minWidth + 'px)').matches) {
            // S'ha de recol·locar a sobre
            // var $target = $content.prevAll(settings.beforeContainer).first();

            // Ens assegurem que s'insereix abans d'un contenidor no buit
            // while ($targetBlock.length > 0 && $targetBlock.children().length === 0 && $targetBlock.text().trim().length === 0) {
            //     $targetBlock = $targetBlock.prevAll(settings.beforeContainer).first();
            // }

            // Només cal recol·locar si s'ha trobat un node previ
            // if ($targetBlock.length > 0) {
            //     $content.insertBefore($targetBlock);

            // if (settings.debug === true) {
            //     $targetBlock.css('background-color', '#f8d7da');
            //     $targetBlock.css('border', '1px solid #f5c6cb');
            // }

            // Reiniciem el clear perquè sinò no s'alineen correctament les columens
            // (el default és clear: left)
            // $targetBlock.css('clear', 'inherit');

            // El problema amb el clear és que si no s'afegeix només es produirà l'alineament correcte del
            // primer paràgraf (perquè forcem el clear), però no hi ha garantia de que aquest
            // sistema sigui correcte en tots els casos, per tant l'afegim com a opció
            // Aquest clear sembla que només s'aplica als paràgraf, i es fa pel CSS (als UL i LI no els
            // afecta)
            // if (settings.forceClear === true) {
            //     $targetBlock.siblings('p').css('clear', 'inherit');
            // } else if (settings.forceClear) {
            //     $targetBlock.siblings(settings.forceClear.query).css('clear', 'inherit');
            // }

            // }
            // } else if (settings.debug === true) {
            // 	// TODO: Afegir classe 'debug'
            //     // No hi ha previ, no cal recol·locar
            //     $target.css('background-color', '#d4edda');
            //     $target.css('border', '1px solid #c3e6cb');
            // }

            // } else {

            // Això s'ha de fer sempre, clonem el node
            var $columnbMobile = $columnb.clone();
            $columnbMobile.addClass('mobile');
            var $contentMobile = $content.clone();
            // TODO: Considerar moure això al css
            $contentMobile.css('cursor', 'pointer');
            // La x només és decorativa, ciclar en qualsevol punt tancará el desplegable
            var $close = jQuery("<span style='color: #5C5C5C; font-weight: bold; float: right; margin-top: -5px'>x</span>");
            $contentMobile.prepend($close);

            // Afegiem el contingut desprès de fer el clone per no duplicar-lo
            // de manera que a columnb queda lligat $content i a columnbMobile queda lligat $contentMobile.
            $columnb.append($content);

            // Alerta! Això és concret pel iocexportl, el zoom s'afegeix dins de functions mitjanaçant
            // previewImage. Només afegim la icona.


            // Ho converti'm en un clicable que es desplega

            // S'ha de mostrar quan es clica
            // var $node = jQuery('<div style="float:' + settings.columnAlign + '"></div>');

            var pattern = /url\("(.*)"\)?/gm;

            // TODO: Comprovar si settings.icon és un objecte i si és així
            // comprovar si hi ha correspondència amb la classe o alguna de les classes

            // ALERTA! La icona s'agafa del content original, el content clonat en aquest punt
            // sembla que no té el css assignat, encara que quan carrega la pàgina sí que hi és
            var icon = '';
            if (settings.icon) {
                icon = settings.icon;
            } else if ($content.css('background-image')) {

                var matches = pattern.exec($content.css('background-image'))
                icon = matches ? matches[1] : false;
            }

            // Si arribat a aquest punt sense icona fem servir el valor per defecte
            if (!icon) {
                console.warn("Using default icon");
                icon = settings.defaultIcon;
            }

            var $img = jQuery('<img src="' + icon + '" style="cursor: pointer">');
            $columnbMobile.append($img);
            $img.addClass(settings.columnAlign);

            // Títol
            var text = $content.text().trim();
            if (text.length>settings.maxTitleLength) {
                text = text.substr(0, settings.maxTitleLength) + "...";
            }

            // Es mostra a la icona
            $img.attr('title', text);

            // Les diferents configuracions es mostraran només en condicions alternes, segons l'amplada de la pantalla
            // i es gestiona mitjançant css mediaqueries
            $columnbMobile.insertAfter($columnb);
            $contentMobile.appendTo($columnbMobile);

            $contentMobile.addClass('hide');

            var toggle = false;

            // Mostra o amaga l'element
            $columnbMobile.on('click', function () {
                toggle = !toggle;

                // ALERTA! L'alineació de la columna oberta no la estem controlant per classe
                if (toggle) {
                    // $columnbMobile.removeClass('hide');
                    $contentMobile.removeClass('hide');
                    $columnbMobile.css('float', 'none');
                    $img.addClass('hide');
                } else {
                    // $columnbMobile.addClass('hide');
                    $columnbMobile.css('float', settings.columnAlign);
                    $contentMobile.addClass('hide');
                    $img.removeClass('hide');
                }


            });

            if (settings.forceIcons) {
                console.log("Forcing icons?", settings.forceIcons);
                $columnb.css('display', 'none');
                $columnbMobile.css('display', 'block');
            }

        });


        return this;
    };

}(jQuery));
