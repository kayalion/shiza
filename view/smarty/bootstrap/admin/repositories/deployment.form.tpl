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
                {call formRows form=$form}

                <div class="form-group">
                    <div class="col-lg-offset-2 col-lg-10">
                        <input type="submit" class="btn btn-default" value="{translate key="button.deploy"}" />
                    {if $referer}
                        <a class="btn" href="{$referer}">{translate key="button.cancel"}</a>
                    {/if}
                    </div>
                </div>
            </fieldset>
        </form>
    </div>
{/block}
