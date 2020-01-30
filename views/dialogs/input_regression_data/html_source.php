<?php

$newstr = $this->base_id.'_new_str';
$newstb = $this->base_id.'_new_stb';
$delstr = $this->base_id.'_del_str';
$delstb = $this->base_id.'_del_stb';

$gen_dialog_datasource = $this->base_id.'_gen_dialog_datasource';

$rec_dialog_datasource = $this->base_id.'_rec_dialog_datasource';

$cur_html_code = <<<HTML

<div >
<div style="display:table;width:100%;">
    <div style="padding:3px;float:left;display:table-cell;">
        <input type="button" style="width:180px;" value="(+) Добавить строку" class="$newstr"/>
    </div>
    <div style="padding:3px;float:left;display:table-cell;">
        <input type="button" style="width:180px;" value="(+) Добавить столбец" class="$newstb"/>
    </div>
</div>
<div style="display:table;width:100%;">
    <div style="padding:3px;float:left;display:table-cell;">
        <input type="button" style="width:180px;" value="(-) Удалить строку" class="$delstr"/>
    </div>
    <div style="padding:3px;float:left;display:table-cell;">
        <input type="button" style="width:180px;" value="(-) Удалить столбец" class="$delstb"/>
    </div>
</div>
<div id="$gen_dialog_datasource" style="display:table;padding:8px;"></div>
<div style="display:table;">
    <input type="button" id="$rec_dialog_datasource" value="Записать данные" />
</div>
</div>

HTML;

return $cur_html_code;

?>