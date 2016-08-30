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
    <p>
        <a href="{$referer}">
            {translate key="button.back.overview"}
        </a>
    </p>

    <div class="clearfix">
    <h3>{translate key="title.backup.restore"}</h3>
    {if !$backups}
    <p>{translate key="label.backup.none"}</p>
    {else}
        {include file="base/form.prototype"}
    <form id="{$restoreForm->getId()}" class="form-horizontal col-md-8" action="{$app.url.request}" method="POST" role="form">
        <fieldset>
            {call formRows form=$restoreForm}

            <div class="form-group">
                <div class="col-lg-offset-2 col-lg-10">
                    <input type="submit" class="btn btn-default" value="{translate key="button.backup.restore"}" />
                </div>
            </div>
        </fieldset>
    </form>
    {/if}
    </div>

    <h3>{translate key="title.backup.create"}</h3>
    <form id="{$createForm->getId()}" class="form-horizontal col-md-8" action="{$app.url.request}" method="POST" role="form">
            <fieldset>
            {call formRows form=$createForm}

            <div class="form-group">
                <div class="col-lg-offset-2 col-lg-10">
                    <input type="submit" class="btn btn-default" value="{translate key="button.backup.create"}" />
                </div>
            </div>
        </fieldset>
    </form>
{/block}
