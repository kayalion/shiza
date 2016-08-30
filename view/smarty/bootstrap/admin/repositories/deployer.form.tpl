{extends file="base/index"}

{block name="head_title" prepend}{$repository->getName()} | {translate key="title.repositories"} | {/block}

{block name="styles" append}
    {style src="bootstrap/css/repository.css"}
{/block}

{block name="content_title"}
    <div class="page-header">
        <h1>
            {translate key="title.repositories"}
            <small>{$repository->getName()}</small>
        </h1>
    </div>
{/block}

{block name="content" append}
    {include file="base/form.prototype"}

    <div class="clearfix">
        <form id="{$form->getId()}" class="form-horizontal col-md-8" action="{$app.url.request}" method="POST" role="form">
        <fieldset>
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active">
                    <a href="#general" aria-controls="general" role="tab" data-toggle="tab">
                        {translate key="title.general"}
                    </a>
                </li>
                <li role="presentation">
                    <a href="#connection" aria-controls="connection" role="tab" data-toggle="tab">
                        {translate key="title.connection"}
                    </a>
                </li>
                <li role="presentation">
                    <a href="#deployment" aria-controls="deployment" role="tab" data-toggle="tab">
                        {translate key="title.deployment"}
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <div role="tabpanel" class="tab-pane" id="deployment">
                {if $form->hasRow('repositoryPath')}
                    {call formRow form=$form row="repositoryPath"}
                {/if}
                {if $form->hasRow('revision')}
                    {call formRow form=$form row="revision"}
                {/if}
                {if $form->hasRow('exclude')}
                    {call formRow form=$form row="exclude"}
                {/if}
                {if $form->hasRow('script')}
                    {call formRow form=$form row="script"}
                {/if}
                </div>
                <div role="tabpanel" class="tab-pane" id="connection">
                    {call formRow form=$form row="remoteHost"}
                    {call formRow form=$form row="remotePort"}
                    {call formRow form=$form row="remoteUsername"}
                {if $form->hasRow('useKey')}
                    {call formRow form=$form row="useKey"}
                {/if}
                    {call formRow form=$form row="newPassword"}
                {if $form->hasRow('remotePath')}
                    {call formRow form=$form row="remotePath"}
                {/if}
                </div>
                <div role="tabpanel" class="tab-pane active" id="general">
                    {call formRows form=$form}
                </div>
            </div>

            <div class="form-group">
                <div class="col-lg-offset-2 col-lg-10">
                    <input type="submit" class="btn btn-default" value="{if $deployer->getId()}{translate key="button.save"}{else}{translate key="button.add"}{/if}" />
                {if $referer}
                    <a class="btn" href="{$referer}">{translate key="button.cancel"}</a>
                {/if}
                </div>
            </div>
        </fieldset>
        </form>
    </div>
{/block}

{block name="scripts" append}
    {script src="bootstrap/js/jquery-ui.js"}
    {script src="bootstrap/js/form.js"}
{/block}
