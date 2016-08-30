{extends file="base/index"}

{block name="head_title" prepend}{if $keyChain->getId()}{$keyChain->getName()} | {/if}{translate key="title.key-chains"} | {/block}

{block name="content_title"}
    <div class="page-header">
        <h1>
            {translate key="title.key-chains"}
            <small>{if $keyChain->getId()}{$keyChain->getName()}{else}{translate key="title.key-chain.add"}{/if}</small>
        </h1>
    </div>
{/block}

{block name="content" append}
    {include file="base/form.prototype"}

    <form id="{$form->getId()}" class="form-horizontal col-md-8" action="{$app.url.request}" method="POST" role="form">
        <fieldset>
            {call formRows form=$form}

            <div class="form-group">
                <div class="col-lg-offset-2 col-lg-10">
                    <input type="submit" class="btn btn-default" value="{if $keyChain->getId()}{translate key="button.update"}{else}{translate key="button.add"}{/if}" />
                {if $referer}
                    <a class="btn" href="{$referer}">{translate key="button.cancel"}</a>
                {/if}
                </div>
            </div>
        </fieldset>
    </form>
{/block}
