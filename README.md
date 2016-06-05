# Bad Behavior plugin for b2evolution #

## How to Install ##
This only includes the [b2evolution](http://b2evolution.net/)-specific parts of [Bad Behavior](http://bad-behavior.ioerror.us/download/).  For it to actually work, you'll have to to get the Bad Behavior files.  Here's good strategy for getting a [complete installation](#instal) (in the root b2evolution directory).

Figure 1 shows the [necessary directory layout](#fig1).

## Kiel instali ##
Ĉi tio estas nur kromprogramo por [b2evolution](http://b2evolution.net/). Por funkciigi ĝin oni devas atingi la dosierojn de [Bad Behavior](http://bad-behavior.ioerror.us/download/). Jen [maniero por plena instalaĵo](#instal) en la radika dosiero de b2evolution.

Jen [la aranĝo de via b2evolution-instalaĵo](#fig1).

### Getting the Bad Behavior files/Atingado de la dosieroj de Bad Behavior ###
<pre id="instal"><kbd>
cd plugins
git clone https://github.com/keithbowes/bad_behaviour_plugin.git
cd bad_behaviour_plugin
svn co https://plugins.svn.wordpress.org/bad-behavior/trunk/ .
</kbd></pre>

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
