#@Powershell -NoP -W Hidden -C "$PSScriptRoot='%~dp0'.TrimEnd('\');&([ScriptBlock]::Create((gc '%~f0'|?{$_.ReadCount -gt 1}|Out-String)))" & exit/b
if ($PSScriptRoot) {
    $code = '[DllImport("user32.dll")]public static extern bool ShowWindowAsync(IntPtr hWnd,int nCmdShow);'
    $type = Add-Type -MemberDefinition $code -Name Win32ShowWindowAsync -PassThru
    $type::ShowWindowAsync((Get-Process -PID $PID).MainWindowHandle,0) | Out-Null # SW_HIDE => hide window
}
#----------------------- EDIT HERE -------------------------
$script:excelFile = "$script:PSScriptRoot\books.xlsx"
$AWSAccessKey = "AKIAIOSFODNN7EXAMPLE"
$secretkey    = "1234567890"
$AssociateTag = "mytag-20"
#-----------------------------------------------------------

$hmacsha = New-Object System.Security.Cryptography.HMACSHA256
$hmacsha.key = [Text.Encoding]::ASCII.GetBytes($secretkey)
$params = @( "AWSAccessKeyId=$AWSAccessKey",
             "AssociateTag=$AssociateTag",
             "Condition=New",
             "IdType=ISBN",
             "dummy",
             "Operation=ItemLookup",
             "Power=binding:not kindle".Replace(":","%3A").Replace(" ","%20"),
             "ResponseGroup=ItemAttributes,OfferSummary".Replace(",","%2C"),
             "SearchIndex=Books",
             "Service=AWSECommerceService",
             "dummy" )

function Get-BookAttributes($ISBN) {
    $ErrorActionPreference = "Stop"
    if ($AWSAccessKey -match "EXAMPLE") {

        if($ISBN.Substring(0,3) -eq "978"){
        
            $xml = Invoke-RestMethod "https://www.googleapis.com/books/v1/volumes?q=ISBN:$ISBN"
            $title = $xml.items[0].volumeInfo.title
            $today = Get-Date -Format "yyyy-MM-dd"
            $authors = $xml.items[0].volumeInfo.authors -Join ","
            $publisher = $xml.items[0].volumeInfo.publisher
            $pdate = $xml.items[0].volumeInfo.publishedDate
            $description = $xml.items[0].volumeInfo.description
            $thumbnail = $xml.items[0].volumeInfo.imageLinks.thumbnail
            return  [PSCustomObject]@{Selected=$False;ISBN=$ISBN;Title=$title;Authors=$authors;Publisher=$publisher;PDate=$pdate;Date=$today;Desc=$description;Thum=$thumbnail}

        } else{ 
            $xml = Invoke-RestMethod "https://www.googleapis.com/books/v1/volumes?q=$ISBN"
            $title = $xml.items[0].volumeInfo.title
            $today = Get-Date -Format "yyyy-MM-dd"
            $authors = $xml.items[0].volumeInfo.authors -Join ","
            $publisher = $xml.items[0].volumeInfo.publisher
            $pdate = $xml.items[0].volumeInfo.publishedDate
            $description = $xml.items[0].volumeInfo.description
            $thumbnail = $xml.items[0].volumeInfo.imageLinks.thumbnail

            if ($xml.items[0].volumeInfo.industryIdentifiers.type[0] -eq "ISBN_13"){
                $ISBN = $xml.items[0].volumeInfo.industryIdentifiers.identifier[0]
            } else {
                $ISBN = $xml.items[0].volumeInfo.industryIdentifiers.identifier[1]
            }
            return  [PSCustomObject]@{Selected=$False;ISBN=$ISBN;Title=$title;Authors=$authors;Publisher=$publisher;PDate=$pdate;Date=$today;Desc=$description;Thum=$thumbnail}
        }
    }
    $params[4]  = "ItemId=" + $ISBN
    $params[10] = "Timestamp=" + (Get-Date -Format "yyyy-MM-ddTHH:mm:ss.000Z").Replace(":","%3A")
    $urlParams  = $params -Join "&"
    $strtosign  = "GET`nwebservices.amazon.co.jp`n/onca/xml`n$urlParams"
    $signature  = $hmacsha.ComputeHash([Text.Encoding]::ASCII.GetBytes($strtosign))
    $signature  = [Convert]::ToBase64String($signature)
    $signature  = $signature.Replace("+","%2B").Replace("=","%3D")
    $url = "http://webservices.amazon.co.jp/onca/xml?" + $urlParams + "&Signature=" + $signature
    $it=0
    try { $xml = Invoke-RestMethod $url } catch {}
    while (-not $xml -and $it -lt 5) {
        $it++
        Start-Sleep -Milliseconds 500
        try { $xml = Invoke-RestMethod $url } catch {}
    }
    if (-not $xml) {
        throw
    }
    $item      = $xml.getElementsByTagName("Item")[0]
    $title     = $item.ItemAttributes.Title
    $authors   = $item.ItemAttributes.Author -Join ","
    $publisher = $item.ItemAttributes.Manufacturer
    $pdate     = $item.ItemAttributes.PublicationDate
    $price     = $item.OfferSummary.LowestNewPrice.Amount -Replace "(.)(?=(...)+$)",'$1,'
    $today     = Get-Date -Format "yyyy-MM-dd"
    return  [PSCustomObject]@{Selected=$False;ISBN=$ISBN;Title=$title;Authors=$authors;Publisher=$publisher;PDate=$pdate;Price=$price;Date=$today}
}

Add-Type -AssemblyName PresentationFramework

[xml]$xaml = @"
    <Window xmlns="http://schemas.microsoft.com/winfx/2006/xaml/presentation"
            xmlns:x="http://schemas.microsoft.com/winfx/2006/xaml"
            Title="書籍情報登録" Height="600" Width="800" MinHeight="230" MinWidth="670" FocusManager.FocusedElement="{Binding ElementName=TextBox}">
        <Grid>
            <Label Name="Label" Content="ISBN-13" Margin="10,5,0,0" Height="35" Width="100" HorizontalAlignment="Left" VerticalAlignment="Top" VerticalContentAlignment="Center" FontSize="20"/>
            <TextBox Name="TextBox" Text="978" Margin="110,10,0,0"  Height="28" Width="200" HorizontalAlignment="Left" VerticalAlignment="Top" VerticalContentAlignment="Center" FontSize="20" SelectionStart="4"/>
            <Button Name="SearchButton" Content="検索"   Margin="320,10,0,0" Height="28" Width="70" HorizontalAlignment="Left"  VerticalAlignment="Top" VerticalContentAlignment="Center" FontSize="20"/>
            <Button Name="ClearButton"  Content="クリア" Margin="400,10,0,0" Height="28" Width="70" HorizontalAlignment="Left"  VerticalAlignment="Top" VerticalContentAlignment="Center" FontSize="20"/>
            <Button Name="DeleteButton" Content="行削除" Margin="480,10,0,0" Height="28" Width="70" HorizontalAlignment="Left"  VerticalAlignment="Top" VerticalContentAlignment="Center" FontSize="20"/>
            <Button Name="SaveButton"   Content="保存"   Margin="0,10,100,0" Height="28" Width="70" HorizontalAlignment="Right" VerticalAlignment="Top" VerticalContentAlignment="Center" FontSize="20"/>
            <Button Name="ExitButton"   Content="終了"   Margin="0,10,20,0"  Height="28" Width="70" HorizontalAlignment="Right" VerticalAlignment="Top" VerticalContentAlignment="Center" FontSize="20"/>
            <DataGrid Name="DataGrid" Margin="10,60,10,25" HorizontalAlignment="Stretch" VerticalAlignment="Stretch" FontSize="14" AutoGenerateColumns="False"
                CanUserDeleteRows="False">
                <DataGrid.Columns>
                    <DataGridTemplateColumn Header="選択">
                        <DataGridTemplateColumn.CellTemplate>
                            <DataTemplate>
                                <CheckBox IsChecked="{Binding Selected,Mode=TwoWay,UpdateSourceTrigger=PropertyChanged}" HorizontalAlignment="Center" VerticalAlignment="Center"/>
                            </DataTemplate>
                        </DataGridTemplateColumn.CellTemplate>
                    </DataGridTemplateColumn>
                    <DataGridTextColumn Binding="{Binding Title}"     Header="タイトル"/>
                    <DataGridTextColumn Binding="{Binding ISBN}"      Header="ISBN"/>
                    <DataGridTextColumn Binding="{Binding Authors}"   Header="著者"/>
                    <DataGridTextColumn Binding="{Binding Publisher}" Header="出版社"/>
                    <DataGridTextColumn Binding="{Binding PDate}"     Header="出版日"/>
                    <DataGridTextColumn Binding="{Binding Date}"      Header="登録日"/>
                    <DataGridTextColumn Binding="{Binding Desc}"      Header="説明"/>
                    <DataGridTextColumn Binding="{Binding Thum}"      Header="サムネイルURL"/>
                </DataGrid.Columns>
            </DataGrid>
            <TextBlock Name="Message" Text="" Margin="20,0,20,3" HorizontalAlignment="Stretch" VerticalAlignment="Bottom" FontSize="14"/>
        </Grid>
    </Window>
"@

$window = [Windows.Markup.XamlReader]::Load((New-Object System.Xml.XmlNodeReader $xaml))

$clearButton  = $window.FindName("ClearButton")
$dataGrid     = $window.FindName("DataGrid")
$deleteButton = $window.FindName("DeleteButton")
$exitButton   = $window.FindName("ExitButton")
$message      = $window.FindName("Message")
$saveButton   = $window.FindName("SaveButton")
$searchButton = $window.FindName("SearchButton")
$textBox      = $window.FindName("TextBox")

$dataGrid.add_MouseRightButtonUp({ # 右クリックメニュー無効化
    Param($s,$e)
    if ($s.CurrentColumn.DisplayIndex -eq 6) { return }
    $e.Handled = $True
})

$dataGrid.add_PreviewKeyDown({ # コピー以外のキー操作無効化
    param($s,$e)
    if ($s.CurrentColumn.DisplayIndex -eq 6) { return }
    if ($e.KeyboardDevice.Modifiers -eq "Ctrl" -and $e.Key -eq "C") { return }
    $e.Handled = $True
})

$collection = New-Object "System.Collections.ObjectModel.ObservableCollection[Object]"
$dataGrid.ItemsSource = $collection

$clearButtonClick = {
    $message.Text = ""
    $textBox.Text = "978"
    $textBox.SelectionStart  = 3
    $textBox.SelectionLength = 0
    $textBox.Focus()
}

$clearButton.Add_Click($clearButtonClick)

$deleteButton.Add_Click({
    $message.Text = ""
    $selected = $collection | ? { $_.Selected }
    if ($selected) {
        $result = [Windows.MessageBox]::Show("選択された項目を削除しますか？", "確認", "YesNo", "Question","No")
        if ($result -eq "Yes") {
            for($i=$collection.Count-1; $i -ge 0; $i--) {
                if ($collection[$i].Selected) { $collection.RemoveAt($i) }
            }
        }
    } else {
        $message.Foreground = "Red"
        $message.Text = "削除対象のアイテムが選択されていません。"
    }
})

$exitButton.Add_Click({ $window.Close() })

$window.Add_Closing({
    $message.Text = ""
    if ($collection.Count -gt 0) {
        $result = [Windows.MessageBox]::Show("データが保存されていません。`n本当に終了しますか？", "確認", "YesNo", "Question","No")
        if ($result -eq "No") {
            $_.Cancel = $True
            $textBox.Focus()
            return
        }
        $collection.Clear()
    }
    $ps1.Dispose()
    $ps2.Dispose()
    $runspace.Dispose()
})

$saveButton.Add_Click({
    $message.Text = ""
    if ($collection.Count -gt 0) {
        $sfd = New-Object Microsoft.Win32.SaveFileDialog
        $sfd.DefaultExt       = ".xlsx"
        $sfd.Filename         = $excelFile
        $sfd.Filter           = "エクセルファイル (*.xlsx)|*.xlsx"
        $sfd.OverwritePrompt  = $False
        $result = $sfd.ShowDialog()
        if ($result -eq "True") {
            $script:excelFile = $sfd.Filename
            $ps1.BeginInvoke()
        }
    } else {
        $message.Foreground = "Red"
        $message.Text = "保存するデータがありません。"
    }
    $textBox.Focus()
})

$searchButtonClick = {
    if ($textBox.Text -match '^[0-9]{13}$') {
        $registered = $collection | ? { $_.ISBN -eq  $textBox.Text }
        if ($registered) {
            $message.Foreground = "Red"
            $message.Text = "指定されたISBN番号の書籍は登録済みです。"
        } else {
            $ps2.BeginInvoke()
        }
    } else {
        if ($textBox.Text.Substring(0,3) -eq "978"){
            $message.Foreground = "Red"
            $message.Text = "ISBN番号は数字13桁で指定してください。"
        } else {
            $ps2.BeginInvoke()
        }
    }
    $textBox.Focus()
}

$searchButton.Add_Click($searchButtonClick)

$textBox.add_KeyDown({ 
    if ($_.Key -eq "Enter") { 
        $textBox.Text = $textBox.Text -Replace '^(?:978)?(978[0-9]{10})$','$1'
        & $searchButtonClick
        $_.Handled = $True
    } 
})

function Export-Excel ($FilePath) {
    $ErrorActionPreference = "Stop"
    $excel = New-Object -ComObject Excel.Application
    $excel.Visible = $False
    $excel.DisplayAlerts = $False
    $top  = 3
    $left = 2
    $row = $top
    $col = $left
    try {
        if ((Test-Path $FilePath)) {
            $workbook = $excel.Workbooks.Open($FilePath)
            $sheet = $excel.Worksheets.Item(1)
        } else {
            $workbook = $excel.Workbooks.Add()
            $workbook.SaveAs($FilePath)
            $sheet = $excel.Worksheets.Item(1)
            "No.","タイトル","ISBN","著者","出版社","出版日","登録日","説明","サムネイルURL" | % { 
                $sheet.Cells.Item($row,$col).Value = $_
                $sheet.Cells.Item($row,$col).HorizontalAlignment = -4108  # xlHAlignCenter
                $sheet.Cells.Item($row,$col).Interior.ColorIndex = 19
                $col++
            }
            $row++
            $col = $left
        }
        while ($sheet.Cells.Item($row,$col).Text) { $row++ }
        foreach ($item in $dataGrid.Items) { 
            $sheet.Cells.Item($row,$col++).Formula = "=Row()-$top"

            #リンク付きタイトル名に
            $sheet.Cells.Item($row,$col++).Formula = "=HYPERLINK(""" + $item.Thum + """,""" + $item.Title + """)"
            #$sheet.Cells.Item($row,$col++).Value = $item.Title

            # $item.Title,$item.Authors,$item.Publisher,$item.PDate,$item.Price,$item.Date | % { $sheet.Cells.Item($row,$col++).Value = $_ }
            $item.ISBN,$item.Authors,$item.Publisher,$item.PDate,$item.Date,$item.Desc,$item.Thum | % { $sheet.Cells.Item($row,$col++).Value = $_ }
            $row++
            $col = $left 
        }
        $range = $sheet.Range( $sheet.Cells.Item($top,$left), $sheet.Cells.Item($row-1,$left+8) )
        $range.Borders.LineStyle = 1
        $range.EntireColumn.AutoFit() | Out-Null        

        $range2 = $sheet.Range( $sheet.Cells.Item($top+1,$left+2), $sheet.Cells.Item($row-1,$left+2) )
        $range2.NumberFormatLocal ="0"

        $workbook.Close($True)
    } catch {
        throw
    } finally {
        if ($excel) { $excel.Quit() }
        $range,$sheet,$workbook,$excel | % { try{[Runtime.Interopservices.Marshal]::ReleaseComObject($_)}catch{}; $_ = $Null } | Out-Null
        [GC]::Collect()
    }
}

$syncHash = [hashtable]::Synchronized(@{})
$syncHash.action = {
    param($cmd,$keyword,$msg,$popup)
    function dispatch($strCmd) { $syncHash.dataGrid.Dispatcher.Invoke([ScriptBlock]::Create($strCmd)) }
    try {
        dispatch ('$message.Foreground = "Black";'                      +
                  '$message.Text = "' + $msg + '";'                     +
                  '$searchButton, $clearButton, $deleteButton, $saveButton, $exitButton | % { $_.IsEnabled = $False }')
        dispatch ('& ' + $cmd + ';'                                     +
                  '$message.Foreground = "Green";'                      +
                  '$message.Text = "' + $keyword + 'に成功しました。"')
    } catch {
        dispatch ('$message.Foreground = "Red";'                        +
                  '$message.Text = "' + $keyword + 'に失敗しました。"')
        if ($popup) { dispatch ('[Windows.MessageBox]::Show("' + $keyword + 'に失敗しました。", "失敗", "OK", "Error") | Out-Null') }
    } finally {
        dispatch '$searchButton, $clearButton, $deleteButton, $saveButton, $exitButton | % { $_.IsEnabled = $True }'
    }
}
$syncHash.dataGrid = $dataGrid
$runspace = [RunspaceFactory]::CreateRunspace()
$runspace.ApartmentState = "STA"
$runspace.ThreadOptions  = "ReuseThread"
$runspace.Open()
$runspace.SessionStateProxy.SetVariable("syncHash",$syncHash)
$saveCmd   = { $dataGrid.Items | Export-Excel $excelFile; $collection.Clear() }
$searchCmd = { 
    $book = Get-BookAttributes($textBox.Text)
    if ($book.Title) {
        $collection.Add($book)
        & $clearButtonClick
    } else {
        $message.Foreground = "Red"
        $message.Text = "指定されたISBN番号に対応するデータが存在しません。"
    }
}
$action1 = { & $syncHash.action '$saveCmd'   '保存' 'データを $excelFile に保存しています。' 1 }
$action2 = { & $syncHash.action '$searchCmd' '書籍情報の取得' 'ISBN番号 $($textBox.Text) を検索しています。' 0 }
$ps1 = [PowerShell]::Create().AddScript($action1)
$ps2 = [PowerShell]::Create().AddScript($action2)
$ps1.runspace = $runspace
$ps2.runspace = $runspace

$window.ShowDialog() | Out-Null