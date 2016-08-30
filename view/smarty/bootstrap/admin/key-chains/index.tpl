{extends file="base/index"}

{block name="head_title" prepend}{translate key="title.key-chains"} | {/block}

{block name="content_title"}
    <div class="page-header">
        <h1>{translate key="title.key-chains"}</h1>
    </div>
{/block}

{block name="content" append}
    <div class="btn-group">
        <a href="{url id="key-chains.add"}?referer={$app.url.request|urlencode}" class="btn btn-default">
            <span class="glyphicon glyphicon-plus"></span> {translate key="button.key-chain.add"}
        </a>
        <a href="{url id="ssh-keys"}" class="btn btn-default">
            <span class="glyphicon glyphicon-lock"></span> {translate key="button.ssh-keys.manage"}
        </a>
    </div>

    <p></p>

    {if $keyChains}
    <table class="table table-responsive table-striped">
        <thead>
            <tr>
                <th class="action"></th>
                <th>{translate key="label.key-chain"}</th>
            </tr>
        </thead>
        <tbody>
        {foreach $keyChains as $keyChain}
            <tr>
                <td class="action">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="glyphicon glyphicon-cog"></span> <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="{url id="key-chains.edit" parameters=["id" => $keyChain->getId()]}?referer={$app.url.request|urlencode}">
                                    {translate key="button.edit"}
                                </a>
                            </li>
                            <li>
                                <a href="{url id="key-chains.delete" parameters=["id" => $keyChain->getId()]}?referer={$app.url.request|urlencode}">
                                    {translate key="button.delete"}
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
                <td>
                    <a href="{url id="key-chains.edit" parameters=["id" => $keyChain->getId()]}?referer={$app.url.request|urlencode}">
                        {$keyChain->getName()}
                    </a>
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
    {else}
    <p>{translate key="label.key-chains.none"}</p>
    {/if}
{/block}
