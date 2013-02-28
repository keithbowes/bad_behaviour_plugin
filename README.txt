HOW TO INSTALL
This only includes the b2evolution-specific parts of Bad Behavior.  For it to actually work, you'll have to to get the Bad Behavior files from elsewhere [1].  That includes copying the bad-behavior directory and the bad-behavior-mysql.php, settings.ini, and whitelist.ini files into this directory (rename settings-sample.ini and whitelist-sample.ini to settings.ini and whitelist.ini, respectively, and edit to suit your needs).

Your b2evolution layout should be something like in [2].

KIEL INSTALI
Ĉi tio estas nur kromprogramo por b2evolution. Por funkciigi ĝin vi devas elŝuti la dosierojn el la TTT-ejo de Bad Behavior [1]. Vi devas kopii la jenajn dosierojn en ĉi tiun dosieron: bad-behavior-mysql.php, settings,ini kaj whitelist.ini (alinomu la dosierojn settings-sample.ini kaj whitelist-sample.ini al settings.ini kaj whitelist.ini, kaj poste redakti ilin laŭplaĉe).

La aranĝo de via b2evolution-instalaĵo devas esti kiel en [2].

[1] http://bad-behavior.ioerror.us/download/
[2]
- b2evolution
  - blogs
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
        - settings.ini
        - whitelist.ini
