<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Handlebars.JS</title>
</head>
<body>
    <script type="text/template" id="tpl1">
        Hello world, {{name}}
    </script>

    <script type="text/template" id="tpl2">
        Hello world, {{{sayMyName 'Mr.'}}} by {{name}}
    </script>

    <script type="text/javascript" src="jquery.js"></script>
    <script type="text/javascript" src="handlebars.js"></script>
    <script type="text/javascript">
        (function() {
            var tpl1 = Handlebars.compile($('#tpl1').html()),
                result;

            result = tpl1({name: 'Ganesha'});
            console.log(result);
        })();

        (function() {

            var tpl2 = Handlebars.compile($('#tpl2').html()),
                result;

            Handlebars.registerHelper('sayMyName', function(prefix) {
                return prefix + ' reekoheek (' + new Date() + ')';
            });

            result = tpl2({name:'jafar'});
            console.log(result);

            setTimeout(function() {

                result = tpl2({name:'jafar'});
                console.log(result);
            }, 2000);
        })();
    </script>
</body>
</html>