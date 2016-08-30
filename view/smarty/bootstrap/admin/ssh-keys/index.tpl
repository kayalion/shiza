{extends file="base/index"}

{block name="head_title" prepend}{translate key="title.ssh-keys"} | {/block}

{block name="content_title"}
    <div class="page-header">
        <h1>{translate key="title.ssh-keys"}</h1>
    </div>
{/block}

{block name="content" append}
    <div class="btn-group">
        <a href="{url id="ssh-keys.add"}?referer={$app.url.request|urlencode}" class="btn btn-default">
            <span class="glyphicon glyphicon-plus"></span> {translate key="button.ssh-key.add"}
        </a>
        <a href="{url id="key-chains"}" class="btn btn-default">
            <span class="glyphicon glyphicon-lock"></span> {translate key="button.key-chains.manage"}
        </a>
    </div>

    <p></p>

    <table class="table table-responsive table-striped">
        <thead>
            <tr>
                <th class="action"></th>
                <th>{translate key="label.ssh-key"}</th>
            </tr>
        </thead>
        <tbody>
    {foreach $sshKeys as $sshKey}
            <tr>
                <td class="action">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="glyphicon glyphicon-cog"></span> <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="{url id="ssh-keys.edit" parameters=["id" => $sshKey->getId()]}?referer={$app.url.request|urlencode}">
                                    {translate key="button.edit"}
                                </a>
                            </li>
                            <li>
                                <a href="{url id="ssh-keys.delete" parameters=["id" => $sshKey->getId()]}?referer={$app.url.request|urlencode}">
                                    {translate key="button.delete"}
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
                <td>
                    <a href="{url id="ssh-keys.edit" parameters=["id" => $sshKey->getId()]}?referer={$app.url.request|urlencode}">
                        {$sshKey->getName()}
                    </a>
                    <br>
                    <small>{$sshKey->getPublicKeyTruncated()}</small>
                </td>
            </tr>
    {/foreach}
        </tbody>
    </table>
{/block}
