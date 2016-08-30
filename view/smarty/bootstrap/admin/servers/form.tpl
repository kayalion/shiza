{extends file="base/index"}

{block name="head_title" prepend}{if $server->getId()}{$server->getHost()} | {/if}{translate key="title.servers"} | {/block}

{block name="content_title"}
    <div class="page-header">
        <h1>
            {translate key="title.servers"}
            <small>{if $server->getId()}{$server->getHost()}{else}{translate key="title.server.add"}{/if}</small>
        </h1>
    </div>
{/block}

{block name="content" append}
    {include file="base/form.prototype"}

    <form id="{$form->getId()}" class="form-horizontal col-md-8" action="{$app.url.request}" method="POST" role="form">
        <fieldset>
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active">
                    <a href="#general" aria-controls="general" role="tab" data-toggle="tab">
                        {translate key="title.general"}
                    </a>
                </li>
                <li role="presentation">
                    <a href="#web" aria-controls="web" role="tab" data-toggle="tab">
                        {translate key="title.web"}
                    </a>
                </li>
                <li role="presentation">
                    <a href="#database" aria-controls="database" role="tab" data-toggle="tab">
                        {translate key="title.database"}
                    </a>
                </li>
                <li role="presentation">
                    <a href="#cron" aria-controls="cron" role="tab" data-toggle="tab">
                        {translate key="title.cron"}
                    </a>
                </li>
                <li role="presentation">
                    <a href="#varnish" aria-controls="varnish" role="tab" data-toggle="tab">
                        {translate key="title.varnish"}
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <div role="tabpanel" class="tab-pane" id="web">
                    {call formRow form=$form row="isWeb"}
                    {call formRow form=$form row="webManager"}
                </div>
                <div role="tabpanel" class="tab-pane" id="database">
                    {call formRow form=$form row="isDatabase"}
                    {call formRow form=$form row="databaseManager"}
                    {call formRow form=$form row="databaseUsername"}
                    {call formRow form=$form row="databasePassword"}
                </div>
                <div role="tabpanel" class="tab-pane" id="cron">
                    {call formRow form=$form row="isCron"}
                    {call formRow form=$form row="cronManager"}
                </div>
                <div role="tabpanel" class="tab-pane" id="varnish">
                    {call formRow form=$form row="isVarnish"}
                    {call formRow form=$form row="varnishManager"}
                    {call formRow form=$form row="varnishPort"}
                    {call formRow form=$form row="varnishAdminPort"}
                    {call formRow form=$form row="varnishSecret"}
                    {call formRow form=$form row="varnishMemory"}
                </div>
                <div role="tabpanel" class="tab-pane active" id="general">
                    {call formRows form=$form}
                </div>
            </div>

            <div class="form-group">
                <div class="col-lg-offset-2 col-lg-10">
                    <input type="submit" class="btn btn-default" value="{translate key="button.save"}" />
                {if $referer}
                    <a class="btn" href="{$referer}">{translate key="button.cancel"}</a>
                {/if}
                </div>
            </div>
        </fieldset>
    </form>
{/block}

{block name="scripts" append}
    {script src="bootstrap/js/jquery-ui.js"}
    {script src="bootstrap/js/form.js"}
{/block}
