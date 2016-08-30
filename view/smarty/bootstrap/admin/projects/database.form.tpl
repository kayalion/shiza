{extends file="base/index"}

{block name="head_title" prepend}{$project->getCode()} | {translate key="title.projects"} | {/block}

{block name="content_title"}
    <div class="page-header">
        <h1>
            {translate key="title.projects"}
            <small>{$project->getName()}</small>
        </h1>
    </div>
{/block}

{block name="content" append}
    {include file="base/form.prototype"}

    <form id="{$form->getId()}" class="form-horizontal col-md-8" action="{$app.url.request}" method="POST" role="form">
        <fieldset>
            {call formRow form=$form row="server"}

            <div class="form-group row-name clearfix">
                <label class="col-md-2 control-label" for="form-name">{$form->getRow('name')->getLabel()}</label>
                <div class="col-md-10">
                    <div class="input-group">
                        <div class="input-group-addon">{$project->getUsername()}_</div>
                        {call formWidget form=$form row="name"}
                    </div>
                    <span class="help-block">{$form->getRow('name')->getDescription()}</span>
                </div>
            </div>

            {call formRows form=$form}

            <div class="form-group">
                <div class="col-lg-offset-2 col-lg-10">
                    <input type="submit" class="btn btn-default" value="{if $database->getId()}{translate key="button.update"}{else}{translate key="button.add"}{/if}" />
                {if $referer}
                    <a class="btn" href="{$referer}">{translate key="button.cancel"}</a>
                {/if}
                </div>
            </div>
        </fieldset>
    </form>
{/block}
