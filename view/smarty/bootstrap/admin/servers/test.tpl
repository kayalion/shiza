{extends file="base/index"}

{block name="head_title" prepend}{$server->getHost()} | {translate key="title.servers"} | {/block}

{block name="content_title"}
    <div class="page-header">
        <h1>
            {translate key="title.servers"}
            <small>{$server->getHost()}</h1>
        </h1>
    </div>
{/block}

{block name="content" append}
    <form class="form-horizontal col-md-8" action="{$app.url.request}" method="POST" role="form">
        <p>{translate key="label.server.test.description" server=(string)$server}</p>
        <fieldset>
            <div class="form-group">
                <div class="col-lg-10">
                    <input type="submit" class="btn btn-default" value="{translate key="button.test"}" />
                {if $referer}
                    <a class="btn" href="{$referer}">{translate key="button.cancel"}</a>
                {/if}
                </div>
            </div>
        </fieldset>
    </form>
{/block}
