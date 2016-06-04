## How to Install ##
This only includes the [b2evolution](http://b2evolution.net/)-specific parts of [Bad Behavior](http://bad-behavior.ioerror.us/download/).  For it to
actually work, you'll have to to get the Bad Behavior files from its site.  That includes copying the bad-behavior directory and the bad-behavior-mysql.php files into this directory.

Figure 1 shows the [necessary directory layout](#fig1).

## Kiel instali ##
Ĉi tio estas nur kromprogramo por [b2evolution](http://b2evolution.net/). Por funkciigi ĝin oni devas elŝuti la dosierojn el la TTT-ejo de [Bad Behavior](http://bad-behavior.ioerror.us/download/). Oni devas kopii la dosierujon bad-behavior kaj la dosieron bad-behavior-mysql.php en ĉi tiun dosierujon.

Jen [la aranĝo de via b2evolution-instalaĵo](#fig2).

### Layout/Aranĝo ###
<pre id="fig1">
- b2evolution
    - plugins
        - bad_behaviour_plugin
            - bad-behavior
                - ...
            - locales
                - ...
            - README.txt
            - _bad_behaviour.plugin.php
            - bad-behavior-mysql.php
            - gpl-3.0.txt
</pre>
