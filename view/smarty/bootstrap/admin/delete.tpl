{extends file="base/index"}

{block name="head_title" prepend}{$subtitle} | {translate key=$title} | {/block}

{block name="content_title"}
    <div class="page-header">
        <h1>{translate key=$title} <small>{$subtitle}</small></h1>
    </div>
{/block}

{block name="content" append}
    <p>{translate key="label.delete.description" data=$data}</p>

    {if isset($description)}
        <p>{translate key=$description}</p>
    {/if}

    <form class="form-horizontal" action="{$app.url.request}" method="POST" role="form">
        <fieldset>
            <div class="form-group">
                <div class="col-lg-10">
                    <input type="submit" class="btn btn-danger" value="{translate key="button.delete"}" />
                {if $referer}
                    <a class="btn" href="{$referer}">{translate key="button.cancel"}</a>
                {/if}
                </div>
            </div>
        </fieldset>
    </form>
{/block}
