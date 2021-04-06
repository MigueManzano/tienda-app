<?php
define('FPDF_VERSION','1.7');

class FPDF
{
var $Page;               
var $N;                  
var $Offsets;            
var $Buffer;             
var $Pages;              
var $State;              
var $Compress;           
var $K;                  
var $DefOrientation;     
var $CurOrientation;     
var $StdPageSizes;       
var $DefPageSize;        
var $CurPageSize;        
var $PageSizes;          
var $WPT, $HPT;          
var $W, $H;              
var $LMargin;            
var $TMargin;            
var $RMargin;            
var $BMargin;            
var $CMargin;            
var $X, $Y;              
var $LastH;              
var $LineWidth;          
var $FontPath;           
var $CoreFonts;          
var $Fonts;              
var $FontFiles;          
var $Diffs;              
var $FontFamily;         
var $FontStyle;          
var $Underline;          
var $CurrentFont;        
var $FontSizePt;         
var $FontSize;           
var $DrawColor;          
var $FillColor;          
var $TextColor;          
var $ColorFlag;          
var $WS;                 
var $Images;             
var $PageLinks;          
var $Links;              
var $AutoPageBreak;      
var $PageBreakTrigger;   
var $InHeader;           
var $InFooter;           
var $ZoomMode;           
var $LayoutMode;         
var $Title;              
var $Subject;            
var $Author;             
var $Keywords;           
var $Creator;            
var $AliasNbPages;       
var $PDFVersion;         

function FPDF($Orientation='P', $Unit='mm', $Size='A4')
{
	$this->_DoChecks();
	$this->Page = 0;
	$this->N = 2;
	$this->Buffer = '';
	$this->Pages = array();
	$this->PageSizes = array();
	$this->State = 0;
	$this->Fonts = array();
	$this->FontFiles = array();
	$this->Diffs = array();
	$this->Images = array();
	$this->Links = array();
	$this->InHeader = false;
	$this->InFooter = false;
	$this->LastH = 0;
	$this->FontFamily = '';
	$this->FontStyle = '';
	$this->FontSizePt = 12;
	$this->Underline = false;
	$this->DrawColor = '0 G';
	$this->FillColor = '0 g';
	$this->TextColor = '0 g';
	$this->ColorFlag = false;
	$this->WS = 0;

	if(defined('FPDF_FONTPATH'))
	{
		$this->FontPath = FPDF_FONTPATH;
		if(substr($this->FontPath,-1)!='/' && substr($this->FontPath,-1)!='\\')
			$this->FontPath .= '/';
	}
	elseif(is_dir(dirname(__FILE__).'/Fonts'))
		$this->FontPath = dirname(__FILE__).'/Fonts/';
	else
		$this->FontPath = '';
	$this->CoreFonts = array('Helvetica');
	
	if($Unit==strtolower('PT'))
		$this->K = 1;
	elseif($Unit==strtolower('MM'))
		$this->K = 72/25.4;
	elseif($Unit==strtolower('CM'))
		$this->K = 72/2.54;
	elseif($Unit==strtolower('IN'))
		$this->K = 72;
	else
		$this->Error('Incorrect Unit: '.$Unit);
	
	$this->StdPageSizes = array('A3'=>array(841.89,1190.55), 'A4'=>array(595.28,841.89), 'A5'=>array(420.94,595.28),
		'Letter'=>array(612,792), 'Legal'=>array(612,1008));
	$Size = $this->_GetPageSize($Size);
	$this->DefPageSize = $Size;
	$this->CurPageSize = $Size;
	
	//$Orientation = strtolower($Orientation);
	if($Orientation=='P' || $Orientation=='Portrait')
	{
		$this->DefOrientation = 'P';
		$this->W = $Size[0];
		$this->H = $Size[1];
	}
	elseif($Orientation=='L' || $Orientation=='Landscape')
	{
		$this->DefOrientation = 'L';
		$this->W = $Size[1];
		$this->H = $Size[0];
	}
	else
		$this->Error('Incorrect Orientation: '.$Orientation);
	$this->CurOrientation = $this->DefOrientation;
	$this->WPT = $this->W*$this->K;
	$this->HPT = $this->H*$this->K;
	
	$Margin = 28.35/$this->K;
	$this->SetMargins($Margin,$Margin);
	
	$this->CMargin = $Margin/10;
	
	$this->LineWidth = .567/$this->K;
	
	$this->SetAutoPageBreak(true,2*$Margin);
	
	$this->SetDisplayMode('Default');
	
	$this->SetCompression(true);
	
	$this->PDFVersion = '1.3';
}

function SetMargins($Left, $Top, $Right=null)
{
	$this->LMargin = $Left;
	$this->TMargin = $Top;
	if($Right===null)
		$Right = $Left;
	$this->RMargin = $Right;
}

function SetLeftMargin($Margin)
{
	$this->LMargin = $Margin;
	if($this->Page>0 && $this->X<$Margin)
		$this->X = $Margin;
}

function SetTopMargin($Margin)
{
	$this->TMargin = $Margin;
}

function SetRightMargin($Margin)
{
	$this->RMargin = $Margin;
}

function SetAutoPageBreak($Auto, $Margin=0)
{
	$this->AutoPageBreak = $Auto;
	$this->BMargin = $Margin;
	$this->PageBreakTrigger = $this->H-$Margin;
}

function SetDisplayMode($Zoom, $Layout='Default')
{
	if($Zoom=='Fullpage' || $Zoom=='Fullwidth' || $Zoom=='Real' || $Zoom=='Default' || !is_string($Zoom))
		$this->ZoomMode = $Zoom;
	else
		$this->Error('Incorrect Zoom Display Mode: '.$Zoom);
	if($Layout=='Single' || $Layout=='Continuous' || $Layout=='Two' || $Layout=='Default')
		$this->LayoutMode = $Layout;
	else
		$this->Error('Incorrect Layout Display Mode: '.$Layout);
}

function SetCompression($Compress)
{
	$Variable = 'GZCompress';
	$Variable = strtolower($Variable);
	if(function_exists($Variable))
		$this->Compress = $Compress;
	else
		$this->Compress = false;
}

function SetTitle($Title, $isUTF8=false)
{
	if($isUTF8)
		$Title = $this->_UTF8toUTF16($Title);
	$this->Title = $Title;
}

function SetSubject($Subject, $isUTF8=false)
{
	if($isUTF8)
		$Subject = $this->_UTF8toUTF16($Subject);
	$this->Subject = $Subject;
}

function SetAuthor($Author, $isUTF8=false)
{
	if($isUTF8)
		$Author = $this->_UTF8toUTF16($Author);
	$this->Author = $Author;
}

function SetKeywords($Keywords, $isUTF8=false)
{
	if($isUTF8)
		$Keywords = $this->_UTF8toUTF16($Keywords);
	$this->Keywords = $Keywords;
}

function SetCreator($Creator, $isUTF8=false)
{
	if($isUTF8)
		$Creator = $this->_UTF8toUTF16($Creator);
	$this->Creator = $Creator;
}

function AliasNbPages($Alias='{NB}')
{
	$this->AliasNbPages = $Alias;
}

function Error($Msg)
{
	die('<B> FPDF Error: </B> '.$Msg);
}

function Open()
{
	$this->State = 1;
}

function Close()
{
	if($this->State==3)
		return;
	if($this->Page==0)
		$this->AddPage();
	$this->InFooter = true;
	$this->Footer();
	$this->InFooter = false;
	$this->_EndPage();
	$this->_EndDoc();
}

function AddPage($Orientation='', $Size='')
{
	if($this->State==0)
		$this->Open();
	$Family = $this->FontFamily;
	$Style = $this->FontStyle.($this->Underline ? 'U' : '');
	$Fontsize = $this->FontSizePt;
	$LW = $this->LineWidth;
	$DC = $this->DrawColor;
	$FC = $this->FillColor;
	$TC = $this->TextColor;
	$CF = $this->ColorFlag;
	if($this->Page>0)
	{
		$this->InFooter = true;
		$this->Footer();
		$this->InFooter = false;
		$this->_EndPage();
	}
	$this->_BeginPage($Orientation,$Size);
	$this->_Out('2 J');
	$this->LineWidth = $LW;
	$this->_Out(sprintf('%.2F w',$LW*$this->K));

	if($Family)
		$this->SetFont($Family,$Style,$Fontsize);
	$this->DrawColor = $DC;
	if($DC!='0 G')
		$this->_Out($DC);
	$this->FillColor = $FC;
	if($FC!='0 G')
		$this->_Out($FC);
	$this->TextColor = $TC;
	$this->ColorFlag = $CF;
	$this->InHeader = true;
	$this->Header();
	$this->InHeader = false;
	if($this->LineWidth!=$LW)
	{
		$this->LineWidth = $LW;
		$this->_Out(sprintf('%.2F w',$LW*$this->K));
	}

	if($Family)
		$this->SetFont($Family,$Style,$Fontsize);
	if($this->DrawColor!=$DC)
	{
		$this->DrawColor = $DC;
		$this->_Out($DC);
	}
	if($this->FillColor!=$FC)
	{
		$this->FillColor = $FC;
		$this->_Out($FC);
	}
	$this->TextColor = $TC;
	$this->ColorFlag = $CF;
}

function Header()
{
}

function Footer()
{
}

function PageNo()
{
	return $this->Page;
}

function SetDrawColor($R, $G=null, $B=null)
{
	if(($R==0 && $G==0 && $B==0) || $G===null)
		$this->DrawColor = sprintf('%.3F G',$R/255);
	else
		$this->DrawColor = sprintf('%.3F %.3F %.3F RG',$R/255,$G/255,$B/255);
	if($this->Page>0)
		$this->_Out($this->DrawColor);
}

function SetFillColor($R, $G=null, $B=null)
{
	if(($R==0 && $G==0 && $B==0) || $G===null)
		$this->FillColor = sprintf('%.3F G',$R/255);
	else
		$this->FillColor = sprintf('%.3F %.3F %.3F RG',$R/255,$G/255,$B/255);
	$this->ColorFlag = ($this->FillColor!=$this->TextColor);
	if($this->Page>0)
		$this->_Out($this->FillColor);
}

function SetTextColor($R, $G=null, $B=null)
{
	if(($R==0 && $G==0 && $B==0) || $G===null)
		$this->TextColor = sprintf('%.3F G',$R/255);
	else
		$this->TextColor = sprintf('%.3F %.3F %.3F RG',$R/255,$G/255,$B/255);
	$this->ColorFlag = ($this->FillColor!=$this->TextColor);
}

function GetStringWidth($S)
{
	$S = (string)$S;
	$Var = 'CW';
	$Var = strtolower($Var);
	$CW = &$this->CurrentFont[$Var];
	$W = 0;
	$L = strlen($S);
	for($I=0;$I<$L;$I++)
		$W += $CW[$S[$I]];
	return $W*$this->FontSize/1000;
}

function SetLineWidth($Width)
{
	$this->LineWidth = $Width;
	if($this->Page>0)
		$this->_Out(sprintf('%.2F w',$Width*$this->K));
}

function Line($X1, $Y1, $X2, $Y2)
{
	$this->_Out(sprintf('%.2F %.2F m %.2F %.2F l S',$X1*$this->K,($this->H-$Y1)*$this->K,$X2*$this->K,($this->H-$Y2)*$this->K));
}

function Rect($X, $Y, $W, $H, $Style='')
{
	if($Style=='F')
		$OP = strtolower('F');
	elseif($Style=='FD' || $Style=='DF')
		$OP = 'B';
	else
		$OP = 'S';
	$this->_Out(sprintf('%.2F %.2F %.2F %.2F re %s',$X*$this->K,($this->H-$Y)*$this->K,$W*$this->K,-$H*$this->K,$OP));
}

function AddFont($Family, $Style='', $File='')
{
	//$Family = strtolower($Family);
	if($File=='')
		$File = str_replace(' ','',$Family).strtolower($Style).'.php';
	$Style = strtoupper($Style);
	if($Style=='IB')
		$Style = 'BI';
	$FontKey = $Family.$Style;
	if(isset($this->Fonts[$FontKey]))
		return;
	$Info = $this->_LoadFont($File);
	$Vec = strtolower('I');
	$Info[$Vec] = count($this->Fonts)+1;
	$Aux = strtolower('Diff');
	if(!empty($Info[$Aux]))
	{
		$N = array_search($Info[$Aux],$this->Diffs);
		if(!$N)
		{
			$N = count($this->Diffs)+1;
			$this->Diffs[$N] = $Info[$Aux];
		}
		$Aux2 = strtolower('Diffn');
		$Info[$Aux2] = $N;
	}
	$Aux3 = strtolower('File');
	if(!empty($Info[$Aux3]))
	{
		$Aux4 = strtolower('Type');
		$Aux5 = strtolower('Length1');
		$Aux6 = strtolower('Length2');
		$Aux7 = strtolower('Size1');
		$Aux8 = strtolower('Size2');
		$Aux9 = strtolower('OriginalSize');
		
		if($Info[$Aux4]=='TrueType')
			$this->FontFiles[$Info[$Aux3]] = array($Aux5=>$Info[$Aux9]);
		else
			$this->FontFiles[$Info[$Aux3]] = array($Aux5=>$Info[$Aux7], $Aux6=>$Info[$Aux8]);
	}
	$this->Fonts[$FontKey] = $Info;
}

function SetFont($Family, $Style='', $Size=0)
{
	if($Family=='')
		$Family = $this->FontFamily;
	else
		$Family = $Family;
	$Style = strtoupper($Style);
	if(strpos($Style,'U')!==false)
	{
		$this->Underline = true;
		$Style = str_replace('U','',$Style);
	}
	else
		$this->Underline = false;
	if($Style=='IB')
		$Style = 'BI';
	if($Size==0)
		$Size = $this->FontSizePt;
	if($this->FontFamily==$Family && $this->FontStyle==$Style && $this->FontSizePt==$Size)
		return;
	$FontKey = $Family.$Style;
	if(!isset($this->Fonts[$FontKey]))
	{
		if($Family=='Arial')
			$Family = 'Helvetica';
		if(in_array($Family,$this->CoreFonts))
		{
			if($Family=='Symbol')
				$Style = '';
			$FontKey = $Family.$Style;
			if(!isset($this->Fonts[$FontKey]))
				$this->AddFont($Family,$Style);
		}
		else
			$this->Error('Undefined Font: '.$Family.' '.$Style);
	}
	$this->FontFamily = $Family;
	$this->FontStyle = $Style;
	$this->FontSizePt = $Size;
	$this->FontSize = $Size/$this->K;
	$this->CurrentFont = &$this->Fonts[$FontKey];
	if($this->Page>0)
		$Vec = strtolower('I');
		$this->_Out(sprintf('BT /F%d %.2F Tf ET',$this->CurrentFont[$Vec],$this->FontSizePt));
}

function SetFontSize($Size)
{
	if($this->FontSizePt==$Size)
		return;
	$this->FontSizePt = $Size;
	$this->FontSize = $Size/$this->K;
	if($this->Page>0)
		$Vec = strtolower('I');
		$this->_Out(sprintf('BT /F%d %.2F Tf ET',$this->CurrentFont[$Vec],$this->FontSizePt));
}

function AddLink()
{
	$N = count($this->Links)+1;
	$this->Links[$N] = array(0, 0);
	return $N;
}

function SetLink($Link, $Y=0, $Page=-1)
{
	if($Y==-1)
		$Y = $this->Y;
	if($Page==-1)
		$Page = $this->Page;
	$this->Links[$Link] = array($Page, $Y);
}

function Link($X, $Y, $W, $H, $Link)
{
	$this->PageLinks[$this->Page][] = array($X*$this->K, $this->HPT-$Y*$this->K, $W*$this->K, $H*$this->K, $Link);
}

function Text($X, $Y, $Txt)
{
	$S = sprintf('BT %.2F %.2F Td (%s) Tj ET',$X*$this->K,($this->H-$Y)*$this->K,$this->_Escape($Txt));
	if($this->Underline && $Txt!='')
		$S .= ' '.$this->_DoUnderline($X,$Y,$Txt);
	if($this->ColorFlag)
		$S = 'Q '.$this->TextColor.' '.$S.' Q';
	$this->_Out($S);
}

function AcceptPageBreak()
{
	return $this->AutoPageBreak;
}

function Cell($W, $H=0, $Txt='', $Border=0, $LN=0, $Align='', $Fill=false, $Link='')
{

$Txt=utf8_decode($Txt);
	$K = $this->K;
	if($this->Y+$H>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
	{
		$X = $this->X;
		$WS = $this->WS;
		if($WS>0)
		{
			$this->WS = 0;
			$this->_Out('0 Tw');
		}
		$this->AddPage($this->CurOrientation,$this->CurPageSize);
		$this->X = $X;
		if($WS>0)
		{
			$this->WS = $WS;
			$this->_Out(sprintf('%.3F Tw',$WS*$K));
		}
	}
	if($W==0)
		$W = $this->W-$this->RMargin-$this->X;
	$S = '';
	if($Fill || $Border==1)
	{
		if($Fill)
			$OP = ($Border==1) ? 'B' : strtolower('F');
		else
			$OP = 'S';
		$S = sprintf('%.2F %.2F %.2F %.2F re %s ',$this->X*$K,($this->H-$this->Y)*$K,$W*$K,-$H*$K,$OP);
	}
	if(is_string($Border))
	{
		$X = $this->X;
		$Y = $this->Y;
		if(strpos($Border,'L')!==false)
			$S .= sprintf('%.2F %.2F m %.2F %.2F l S ',$X*$K,($this->H-$Y)*$K,$X*$K,($this->H-($Y+$H))*$K);
		if(strpos($Border,'T')!==false)
			$S .= sprintf('%.2F %.2F m %.2F %.2F l S ',$X*$K,($this->H-$Y)*$K,($X+$W)*$K,($this->H-$Y)*$K);
		if(strpos($Border,'R')!==false)
			$S .= sprintf('%.2F %.2F m %.2F %.2F l S ',($X+$W)*$K,($this->H-$Y)*$K,($X+$W)*$K,($this->H-($Y+$H))*$K);
		if(strpos($Border,'B')!==false)
			$S .= sprintf('%.2F %.2F m %.2F %.2F l S ',$X*$K,($this->h-($Y+$H))*$K,($X+$W)*$K,($this->H-($Y+$H))*$K);
	}
	if($Txt!=='')
	{
		if($Align=='R')
			$DX = $W-$this->CMargin-$this->GetStringWidth($Txt);
		elseif($Align=='C')
			$DX = ($W-$this->GetStringWidth($Txt))/2;
		else
			$DX = $this->CMargin;
		if($this->ColorFlag)
			$S .= 'Q '.$this->TextColor.' ';
		$Txt2 = str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$Txt)));
		$S .= sprintf('BT %.2F %.2F Td (%s) Tj ET',($this->X+$DX)*$K,($this->H-($this->Y+.5*$H+.3*$this->FontSize))*$K,$Txt2);
		if($this->Underline)
			$S .= ' '.$this->_DoUnderline($this->X+$DX,$this->Y+.5*$H+.3*$this->FontSize,$Txt);
		if($this->ColorFlag)
			$S .= ' Q';
		if($Link)
			$this->Link($this->X+$DX,$this->Y+.5*$H-.5*$this->FontSize,$this->GetStringWidth($Txt),$this->FontSize,$Link);
	}
	if($S)
		$this->_Out($S);
	$this->LastH = $H;
	if($LN>0)
	{
		$this->Y += $H;
		if($LN==1)
			$this->X = $this->LMargin;
	}
	else
		$this->X += $W;
}

function MultiCell($W, $H, $Txt, $Border=0, $Align='J', $Fill=false)
{
	$Var = 'CW';
	$Var = strtolower($Var);
	$CW = &$this->CurrentFont[$Var];
	if($W==0)
		$W = $this->W-$this->RMargin-$this->X;
	$WMax = ($W-2*$this->CMargin)*1000/$this->FontSize;
	$S = str_replace("\r",'',$Txt);
	$NB = strlen($S);
	if($NB>0 && $S[$NB-1]=="\n")
		$NB--;
	$B = 0;
	if($Border)
	{
		if($Border==1)
		{
			$Border = 'LTRB';
			$B = 'LRT';
			$B2 = 'LR';
		}
		else
		{
			$B2 = '';
			if(strpos($Border,'L')!==false)
				$B2 .= 'L';
			if(strpos($Border,'R')!==false)
				$B2 .= 'R';
			$B = (strpos($Border,'T')!==false) ? $B2.'T' : $B2;
		}
	}
	$SEP = -1;
	$I = 0;
	$J = 0;
	$L = 0;
	$NS = 0;
	$NL = 1;
	while($I<$NB)
	{
		$C = $S[$I];
		if($C=="\n")
		{
			if($this->WS>0)
			{
				$this->WS = 0;
				$this->_Out('0 Tw');
			}
			$this->Cell($W,$H,substr($S,$J,$I-$J),$B,2,$Align,$Fill);
			$I++;
			$SEP = -1;
			$J = $I;
			$L = 0;
			$NS = 0;
			$NL++;
			if($Border && $NL==2)
				$B = $B2;
			continue;
		}
		if($C==' ')
		{
			$SEP = $I;
			$LS = $L;
			$NS++;
		}
		$L += $CW[$C];
		if($L>$WMax)
		{
			if($SEP==-1)
			{
				if($I==$J)
					$I++;
				if($this->WS>0)
				{
					$this->WS = 0;
					$this->_Out('0 Tw');
				}
				$this->Cell($W,$H,substr($S,$J,$I-$J),$B,2,$Align,$Fill);
			}
			else
			{
				if($Align=='J')
				{
					$this->WS = ($NS>1) ? ($WMax-$LS)/1000*$this->FontSize/($NS-1) : 0;
					$this->_Out(sprintf('%.3F Tw',$this->WS*$this->K));
				}
				$this->Cell($W,$H,substr($S,$J,$SEP-$J),$B,2,$Align,$Fill);
				$I = $SEP+1;
			}
			$SEP = -1;
			$J = $I;
			$L = 0;
			$NS = 0;
			$NL++;
			if($Border && $NL==2)
				$B = $B2;
		}
		else
			$I++;
	}

	if($this->WS>0)
	{
		$this->WS = 0;
		$this->_Out('0 Tw');
	}
	if($Border && strpos($Border,'B')!==false)
		$B .= 'B';
	$this->Cell($W,$H,substr($S,$J,$I-$J),$B,2,$Align,$Fill);
	$this->X = $this->LMargin;
}

function Write($H, $Txt, $Link='')
{
	$Var = 'CW';
	$Var = strtolower($Var);
	$CW = &$this->CurrentFont[$Var];
	$W = $this->W-$this->RMargin-$this->X;
	$WMax = ($W-2*$this->CMargin)*1000/$this->FontSize;
	$S = str_replace("\r",'',$Txt);
	$NB = strlen($S);
	$SEP = -1;
	$I = 0;
	$J = 0;
	$L = 0;
	$NL = 1;
	while($I<$NB)
	{
		$C = $S[$I];
		if($C=="\n")
		{
			$this->Cell($W,$H,substr($S,$J,$I-$J),0,2,'',0,$Link);
			$I++;
			$SEP = -1;
			$J = $I;
			$L = 0;
			if($NL==1)
			{
				$this->X = $this->LMargin;
				$W = $this->W-$this->RMargin-$this->X;
				$WMax = ($W-2*$this->CMargin)*1000/$this->FontSize;
			}
			$NL++;
			continue;
		}
		if($C==' ')
			$SEP = $I;
		$L += $CW[$C];
		if($L>$WMax)
		{
			if($SEP==-1)
			{
				if($this->X>$this->LMargin)
				{
					$this->X = $this->LMargin;
					$this->Y += $H;
					$W = $this->W-$this->RMargin-$this->X;
					$WMax = ($W-2*$this->CMargin)*1000/$this->FontSize;
					$I++;
					$NL++;
					continue;
				}
				if($I==$J)
					$I++;
				$this->Cell($W,$H,substr($S,$J,$I-$J),0,2,'',0,$Link);
			}
			else
			{
				$this->Cell($W,$H,substr($S,$J,$SEP-$J),0,2,'',0,$Link);
				$I = $SEP+1;
			}
			$SEP = -1;
			$J = $I;
			$L = 0;
			if($NL==1)
			{
				$this->X = $this->LMargin;
				$W = $this->W-$this->RMargin-$this->X;
				$WMax = ($W-2*$this->CMargin)*1000/$this->FontSize;
			}
			$NL++;
		}
		else
			$I++;
	}
	if($I!=$J)
		$this->Cell($L/1000*$this->FontSize,$H,substr($S,$J),0,0,'',0,$Link);
}

function Ln($H=null)
{
	$this->X = $this->LMargin;
	if($H===null)
		$this->Y += $this->LastH;
	else
		$this->Y += $H;
}

function Image($File, $X=null, $Y=null, $W=0, $H=0, $Type='', $Link='')
{
	if(!isset($this->Images[$File]))
	{
		if($Type=='')
		{
			$Pos = strrpos($File,'.');
			if(!$Pos)
				$this->Error('Image File No Extension and No Type Specified: '.$File);
			$Type = substr($File,$Pos+1);
		}
		$Type = strtolower($Type);
		$Ext = strtolower('JPEG');
		if($Type==$Ext)
			$Type = strtolower('JPG');
		$PAR = strtolower('_Parse');
		$MTD = $PAR.$Type;
		if(!method_exists($this,$MTD))
			$this->Error('Unsupported Image Type: '.$Type);
		$Info = $this->$MTD($File);
		$Vec = strtolower('I');
		$Info[$Vec] = count($this->Images)+1;
		$this->Images[$File] = $Info;
	}
	else
		$Info = $this->Images[$File];

	if($W==0 && $H==0)
	{
		$W = -96;
		$H = -96;
	}
	if($W<0)
		$W = -$Info[strtolower('W')]*72/$W/$this->K;
	if($H<0)
		$H = -$Info[strtolower('H')]*72/$H/$this->K;
	if($W==0)
		$W = $H*$Info[strtolower('W')]/$Info['h'];
	if($H==0)
		$H = $W*$Info[strtolower('H')]/$Info['w'];

	if($Y===null)
	{
		if($this->Y+$H>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
		{
			$X2 = $this->X;
			$this->AddPage($this->CurOrientation,$this->CurPageSize);
			$this->X = $X2;
		}
		$Y = $this->Y;
		$this->Y += $H;
	}

	if($X===null)
		$X = $this->X;
	$this->_Out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q',$W*$this->K,$H*$this->K,$X*$this->K,($this->H-($Y+$H))*$this->K,$Info[strtolower('I')]));
	if($Link)
		$this->Link($X,$Y,$W,$H,$Link);
}

function GetX()
{
	return $this->X;
}

function SetX($X)
{
	if($X>=0)
		$this->X = $X;
	else
		$this->X = $this->W+$X;
}

function GetY()
{
	return $this->Y;
}

function SetY($Y)
{
	$this->X = $this->LMargin;
	if($Y>=0)
		$this->Y = $Y;
	else
		$this->Y = $this->H+$Y;
}

function SetXY($X, $Y)
{
	$this->SetY($Y);
	$this->SetX($X);
}

function Output($Name='', $Dest='')
{
	if($this->State<3)
		$this->Close();
	$Dest = strtoupper($Dest);
	if($Dest=='')
	{
		if($Name=='')
		{
			$Name = 'Documento.pdf';
			$Dest = 'I';
		}
		else
			$Dest = 'F';
	}
	switch($Dest)
	{
		case 'I':
			$this->_CheckOutput();
			if(PHP_SAPI!=strtolower('CLI'))
			{
				header('Content-Type: Application/PDF');
				header('Content-Disposition: Inline; Filename="'.$Name.'"');
				header('Cache-Control: Private, Max-Age=0, Must-Revalidate');
				header('Pragma: Public');
			}
			echo $this->Buffer;
			break;
		case 'D':
			$this->_CheckOutput();
			header('Content-Type: Application/X-Download');
			header('Content-Disposition: Attachment; Filename="'.$Name.'"');
			header('Cache-Control: Private, Max-Age=0, Must-Revalidate');
			header('Pragma: Public');
			echo $this->Buffer;
			break;
		case 'F':
			$F = fopen($Name,strtolower('WB'));
			if(!$F)
				$this->Error('Unable to Create Output File: '.$Name);
			fwrite($F,$this->Buffer,strlen($this->Buffer));
			fclose($F);
			break;
		case 'S':
			return $this->Buffer;
		default:
			$this->Error('Incorrect Output Destination: '.$Dest);
	}
	return '';
}

function _DoChecks()
{
	if(sprintf('%.1F',1.0)!='1.0')
		$this->Error('This Version of PHP Not Supported');
	if(ini_get(strtolower('MBString.Func_Overload')) & 2)
		$this->Error('MBString Overloading Must Be Disabled');
	if(get_magic_quotes_runtime())
		@set_magic_quotes_runtime(0);
}

function _CheckOutput()
{
	if(PHP_SAPI!=strtolower('CLI'))
	{
		if(headers_sent($File,$Line))
			$this->Error("Some Data has Already Been Output, Can't Send PDF File (Output Started at $File:$Line)");
	}
	if(ob_get_length())
	{
		if(preg_match('/^(\xEF\xBB\xBF)?\s*$/',ob_get_contents()))
		{
			ob_clean();
		}
		else
			$this->Error("Some Data has Already Been Output, Can't Send PDF File");
	}
}

function _GetPageSize($Size)
{
	if(is_string($Size))
	{
		//$Size = strtolower($Size);
		if(!isset($this->StdPageSizes[$Size]))
			$this->Error('Unknown Page Size: '.$Size);
		$A = $this->StdPageSizes[$Size];
		return array($A[0]/$this->K, $A[1]/$this->K);
	}
	else
	{
		if($Size[0]>$Size[1])
			return array($Size[1], $Size[0]);
		else
			return $Size;
	}
}

function _BeginPage($Orientation, $Size)
{
	$this->Page++;
	$this->Pages[$this->Page] = '';
	$this->State = 2;
	$this->X = $this->LMargin;
	$this->Y = $this->TMargin;
	$this->FontFamily = '';
	if($Orientation=='')
		$Orientation = $this->DefOrientation;
	else
		$Orientation = strtoupper($Orientation[0]);
	if($Size=='')
		$Size = $this->DefPageSize;
	else
		$Size = $this->_GetPageSize($Size);
	if($Orientation!=$this->CurOrientation || $Size[0]!=$this->CurPageSize[0] || $Size[1]!=$this->CurPageSize[1])
	{
		if($Orientation=='P')
		{
			$this->W = $Size[0];
			$this->H = $Size[1];
		}
		else
		{
			$this->W = $Size[1];
			$this->H = $Size[0];
		}
		$this->WPT = $this->W*$this->K;
		$this->HPT = $this->H*$this->K;
		$this->PageBreakTrigger = $this->H-$this->BMargin;
		$this->CurOrientation = $Orientation;
		$this->CurPageSize = $Size;
	}
	if($Orientation!=$this->DefOrientation || $Size[0]!=$this->DefPageSize[0] || $Size[1]!=$this->DefPageSize[1])
		$this->PageSizes[$this->Page] = array($this->WPT, $this->HPT);
}

function _EndPage()
{
	$this->State = 1;
}

function _LoadFont($Font)
{
	include($this->FontPath.$Font);
	$A = get_defined_vars();
	if(!isset($A[strtolower('Name')]))
		$this->Error('Could not Include Font Definition File');
	return $A;
}

function _Escape($S)
{
	$S = str_replace('\\','\\\\',$S);
	$S = str_replace('(','\\(',$S);
	$S = str_replace(')','\\)',$S);
	$S = str_replace("\r",'\\r',$S);
	return $S;
}

function _TextString($S)
{
	return '('.$this->_Escape($S).')';
}

function _UTF8toUTF16($S)
{
	$Res = "\xFE\xFF";
	$NB = strlen($S);
	$I = 0;
	while($I<$NB)
	{
		$C1 = ord($S[$I++]);
		if($C1>=224)
		{
			$C2 = ord($S[$I++]);
			$C3 = ord($S[$I++]);
			$Res .= chr((($C1 & 0x0F)<<4) + (($C2 & 0x3C)>>2));
			$Res .= chr((($C2 & 0x03)<<6) + ($C3 & 0x3F));
		}
		elseif($C1>=192)
		{
			$C2 = ord($S[$I++]);
			$Res .= chr(($C1 & 0x1C)>>2);
			$Res .= chr((($C1 & 0x03)<<6) + ($C2 & 0x3F));
		}
		else
		{
			$Res .= "\0".chr($C1);
		}
	}
	return $res;
}

function _DoUnderline($X, $Y, $Txt)
{
	$UP = $this->CurrentFont[strtolower('UP')];
	$UT = $this->CurrentFont[strtolower('UT')];
	$W = $this->GetStringWidth($Txt)+$this->WS*substr_count($Txt,' ');
	return sprintf('%.2F %.2F %.2F %.2F re f',$X*$this->K,($this->H-($Y-$UP/1000*$this->FontSize))*$this->K,$W*$this->K,-$UT/1000*$this->FontSizePt);
}

function _ParseJPG($File)
{
	$A = getimagesize($File);
	$Resp = strtolower('Channels');
	$Bit = strtolower('Bits');
	
	if(!$A)
		$this->Error('Missing or Incorrect Image File: '.$File);
	if($A[2]!=2)
		$this->Error('Not a JPEG File: '.$File);
	if(!isset($A[$Resp]) || $A[$Resp]==3)
		$Colspace = 'DeviceRGB';
	elseif($A[$Resp]==4)
		$Colspace = 'DeviceCMYK';
	else
		$Colspace = 'DeviceGray';
	$BPC = isset($A[$Bit]) ? $A[$Bit] : 8;
	$Data = file_get_contents($File);
	return array(strtolower('W')=>$A[0], strtolower('H')=>$A[1], strtolower('CS')=>$Colspace, strtolower('BPC')=>$BPC, strtolower('F')=>'DCTDecode', 
	strtolower('Data')=>$Data);
}

function _ParsePNG($File)
{
	$F = fopen($File,strtolower('RB'));
	if(!$F)
		$this->Error('Can\'t Open Image File: '.$File);
	$Info = $this->_ParsePNGStream($F,$File);
	fclose($F);
	return $Info;
}

function _ParsePNGStream($F, $File)
{
	if($this->_ReadStream($F,8)!=chr(137).'PNG'.chr(13).chr(10).chr(26).chr(10))
		$this->Error('Not a PNG File: '.$File);

	$this->_ReadStream($F,4);
	if($this->_ReadStream($F,4)!='IHDR')
		$this->Error('Incorrect PNG file: '.$File);
	$W = $this->_ReadInt($F);
	$H = $this->_ReadInt($F);
	$BPC = ord($this->_ReadStream($F,1));
	if($BPC>8)
		$this->Error('16-Bit Depth Not Supported: '.$File);
	$CT = ord($this->_ReadStream($F,1));
	if($CT==0 || $CT==4)
		$Colspace = 'DeviceGray';
	elseif($CT==2 || $CT==6)
		$Colspace = 'DeviceRGB';
	elseif($CT==3)
		$Colspace = 'Indexed';
	else
		$this->Error('Unknown Color Type: '.$File);
	if(ord($this->_ReadStream($F,1))!=0)
		$this->Error('Unknown Compression Method: '.$File);
	if(ord($this->_ReadStream($F,1))!=0)
		$this->Error('Unknown Filter Method: '.$File);
	if(ord($this->_ReadStream($F,1))!=0)
		$this->Error('Interlacing Not Supported: '.$File);
	$this->_ReadStream($F,4);
	$DP = '/Predictor 15 /Colors '.($Colspace=='DeviceRGB' ? 3 : 1).' /BitsPerComponent '.$BPC.' /Columns '.$W;

	$PAL = '';
	$Trns = '';
	$Data = '';
	do
	{
		$N = $this->_ReadInt($F);
		$Type = $this->_ReadStream($F,4);
		if($Type=='PLTE')
		{
			$PAL = $this->_ReadStream($F,$N);
			$this->_ReadStream($F,4);
		}
		elseif($Type=='TRNS')
		{
			$T = $this->_ReadStream($F,$N);
			if($CT==0)
				$Trns = array(ord(substr($T,1,1)));
			elseif($CT==2)
				$Trns = array(ord(substr($T,1,1)), ord(substr($T,3,1)), ord(substr($T,5,1)));
			else
			{
				$Pos = strpos($T,chr(0));
				if($Pos!==false)
					$Trns = array($Pos);
			}
			$this->_ReadStream($F,4);
		}
		elseif($Type=='IDAT')
		{
			$Data .= $this->_ReadStream($F,$N);
			$this->_ReadStream($F,4);
		}
		elseif($Type=='IEND')
			break;
		else
			$this->_ReadStream($F,$N+4);
	}
	while($N);

	if($Colspace=='Indexed' && empty($PAL))
		$this->Error('Missing Palette in '.$File);
	$Info = array(strtolower('W')=>$W, strtolower('H')=>$H, strtolower('CS')=>$Colspace, strtolower('BPC')=>$BPC, strtolower('F')=>'FlateDecode', 
	strtolower('DP')=>$DP, strtolower('PAL')=>$PAL, strtolower('TRNS')=>$Trns);
	if($CT>=4)
	{
		$Acc = strtolower('GZUnCompress');
		if(!function_exists($Acc))
			$this->Error('Zlib Not Available, Can\'t Handle Alpha Channel: '.$File);
		$Data = gzuncompress($Data);
		$Color = '';
		$Alpha = '';
		if($CT==4)
		{
			$LEN = 2*$W;
			for($I=0;$I<$H;$I++)
			{
				$Pos = (1+$LEN)*$I;
				$Color .= $Data[$Pos];
				$Alpha .= $Data[$Pos];
				$Line = substr($Data,$Pos+1,$LEN);
				$Color .= preg_replace('/(.)./s','$1',$Line);
				$Alpha .= preg_replace('/.(.)/s','$1',$Line);
			}
		}
		else
		{
			$LEN = 4*$W;
			for($I=0;$I<$H;$I++)
			{
				$Pos = (1+$LEN)*$I;
				$Color .= $Data[$Pos];
				$Alpha .= $Data[$Pos];
				$Line = substr($Data,$Pos+1,$LEN);
				$Color .= preg_replace('/(.{3})./s','$1',$Line);
				$Alpha .= preg_replace('/.{3}(.)/s','$1',$Line);
			}
		}
		unset($Data);
		$Data = gzcompress($Color);
		$Info[strtolower('Smask')] = gzcompress($Alpha);
		if($this->PDFVersion<'1.4')
			$this->PDFVersion = '1.4';
	}
	$Info[strtolower('Data')] = $Data;
	return $Info;
}

function _ReadStream($F, $N)
{
	$Res = '';
	while($N>0 && !feof($F))
	{
		$S = fread($F,$N);
		if($S===false)
			$this->Error('Error While Reading Stream');
		$N -= strlen($S);
		$Res .= $S;
	}
	if($N>0)
		$this->Error('Unexpected End of Stream');
	return $Res;
}

function _ReadInt($F)
{
	$A = unpack('Ni',$this->_ReadStream($F,4));
	return $A[strtolower('I')];
}

function _ParseGIF($File)
{
	$Pic = strtolower('ImagePNG');
	$Pic2 = strtolower('ImageCreateFromGIF');
	if(!function_exists($Pic))
		$this->Error('GD Extension is Required for GIF Support');
	if(!function_exists($Pic2))
		$this->Error('GD has No GIF Read Support');
	$IM = imagecreatefromgif($File);
	if(!$IM)
		$this->Error('Missing or Incorrect Image File: '.$File);
	imageinterlace($IM,0);
	$F = @fopen(strtolower('php://Temp'), strtolower('RB+'));
	if($F)
	{
		ob_start();
		imagepng($IM);
		$Data = ob_get_clean();
		imagedestroy($IM);
		fwrite($F,$Data);
		rewind($F);
		$Info = $this->_ParsePNGStream($F,$File);
		fclose($F);
	}
	else
	{
		$TMP = tempnam('.',strtolower('Gif'));
		if(!$TMP)
			$this->Error('Unable to Create a Temporary File');
		if(!imagepng($IM,$TMP))
			$this->Error('Error While Saving to Temporary File');
		imagedestroy($IM);
		$Info = $this->_ParsePNG($TMP);
		unlink($TMP);
	}
	return $info;
}

function _NewObj()
{
	$this->N++;
	$this->Offsets[$this->N] = strlen($this->Buffer);
	$this->_Out($this->N.strtolower(' 0 Obj'));
}

function _PutStream($S)
{
	$Cad = strtolower('Stream');
	$Cad2 = strtolower('EndStream');
	$this->_Out($Cad);
	$this->_Out($S);
	$this->_Out($Cad2);
}

function _Out($S)
{
	if($this->State==2)
		$this->Pages[$this->Page] .= $S."\n";
	else
		$this->Buffer .= $S."\n";
}

function _PutPages()
{
	$NB = $this->Page;
	if(!empty($this->AliasNbPages))
	{
		for($N=1;$N<=$NB;$N++)
			$this->Pages[$N] = str_replace($this->AliasNbPages,$NB,$this->Pages[$N]);
	}
	if($this->DefOrientation=='P')
	{
		$WPT = $this->DefPageSize[0]*$this->K;
		$HPT = $this->DefPageSize[1]*$this->K;
	}
	else
	{
		$WPT = $this->DefPageSize[1]*$this->K;
		$HPT = $this->DefPageSize[0]*$this->K;
	}
	$Filter = ($this->Compress) ? '/Filter /FlateDecode ' : '';
	for($N=1;$N<=$NB;$N++)
	{
		$this->_NewObj();
		$this->_Out('<</Type /Page');
		$this->_Out('/Parent 1 0 R');
		if(isset($this->PageSizes[$N]))
			$this->_Out(sprintf('/MediaBox [0 0 %.2F %.2F]',$this->PageSizes[$N][0],$this->PageSizes[$N][1]));
		$this->_Out('/Resources 2 0 R');
		if(isset($this->PageLinks[$N]))
		{
			$Annots = '/Annots [';
			foreach($this->PageLinks[$N] as $PL)
			{
				$Rect = sprintf('%.2F %.2F %.2F %.2F',$PL[0],$PL[1],$PL[0]+$PL[2],$PL[1]-$PL[3]);
				$Annots .= '<</Type /Annot /Subtype /Link /Rect ['.$Rect.'] /Border [0 0 0] ';
				if(is_string($PL[4]))
					$Annots .= '/A <</S /URI /URI '.$this->_TextString($PL[4]).'>>>>';
				else
				{
					$L = $this->Links[$PL[4]];
					$H = isset($this->PageSizes[$l[0]]) ? $this->PageSizes[$l[0]][1] : $HPT;
					$Annots .= sprintf('/Dest [%d 0 R /XYZ 0 %.2F null]>>',1+2*$L[0],$H-$L[1]*$this->K);
				}
			}
			$this->_Out($Annots.']');
		}
		if($this->PDFVersion>'1.3')
			$this->_Out('/Group <</Type /Group /S /Transparency /CS /DeviceRGB>>');
		$this->_Out('/Contents '.($this->N+1).' 0 R>>');
		$Object = strtolower('EndObj');
		$this->_Out($Object);
		$P = ($this->Compress) ? gzcompress($this->Pages[$N]) : $this->Pages[$N];
		$this->_NewObj();
		$this->_Out('<<'.$Filter.'/Length '.strlen($P).'>>');
		$this->_PutStream($P);
		$this->_Out($Object);
	}
	$this->Offsets[1] = strlen($this->Buffer);
	$this->_Out(strtolower('1 0 Obj'));
	$this->_Out('<</Type /Pages');
	$Kids = '/Kids [';
	for($I=0;$I<$NB;$I++)
		$Kids .= (3+2*$I).' 0 R ';
	$this->_Out($Kids.']');
	$this->_Out('/Count '.$NB);
	$this->_Out(sprintf('/MediaBox [0 0 %.2F %.2F]',$WPT,$HPT));
	$this->_Out('>>');
	$this->_Out($Object);
}

function _PutFonts()
{
	$NF = $this->N;
	foreach($this->Diffs as $Diff)
	{
		$this->_NewObj();
		$this->_Out('<</Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences ['.$Diff.']>>');
		$Object = strtolower('EndObj');
		$this->_Out($Object);
	}
	foreach($this->FontFiles as $File=>$Info)
	{
		$this->_NewObj();
		$this->FontFiles[$File][strtolower('N')] = $this->N;
		$Font = file_get_contents($this->FontPath.$File,true);
		if(!$Font)
			$this->Error('Font File Not Found: '.$File);
		$Compressed = (substr($File,-2)==strtolower('.Z'));
		if(!$Compressed && isset($Info[strtolower('Length2')]))
			$Font = substr($Font,6,$Info[strtolower('Length1')]).substr($Font,6+$Info[strtolower('Length1')]+6,$Info[strtolower('Length2')]);
		$this->_Out('<</Length '.strlen($Font));
		if($Compressed)
			$this->_Out('/Filter /FlateDecode');
		$this->_Out('/Length1 '.$Info[strtolower('Length1')]);
		if(isset($Info[strtolower('Length2')]))
			$this->_Out('/Length2 '.$Info[strtolower('Length2')].' /Length3 0');
		$this->_Out('>>');
		$this->_putstream($Font);
		$this->_Out(strtolower('EndObj'));
	}
	foreach($this->Fonts as $K=>$Font)
	{
		$this->Fonts[$K][strtolower('N')] = $this->N+1;
		$Type = $Font[strtolower('Type')];
		$Name = $Font[strtolower('Name')];
		if($Type=='Core')
		{
			$this->_NewObj();
			$this->_Out('<</Type /Font');
			$this->_Out('/BaseFont /'.$Name);
			$this->_Out('/Subtype /Type1');
			if($Name!='Symbol' && $Name!='ZapfDingbats')
				$this->_Out('/Encoding /WinAnsiEncoding');
			$this->_Out('>>');
			$this->_Out(strtolower('EndObj'));
		}
		elseif($Type=='Type1' || $Type=='TrueType')
		{
			$this->_NewObj();
			$this->_Out('<</Type /Font');
			$this->_Out('/BaseFont /'.$Name);
			$this->_Out('/Subtype /'.$Type);
			$this->_Out('/FirstChar 32 /LastChar 255');
			$this->_Out('/Widths '.($this->N+1).' 0 R');
			$this->_Out('/FontDescriptor '.($this->N+2).' 0 R');
			if(isset($Font[strtolower('Diffn')]))
				$this->_Out('/Encoding '.($NF+$Font[strtolower('Diffn')]).' 0 R');
			else
				$this->_Out('/Encoding /WinAnsiEncoding');
			$this->_Out('>>');
			$this->_Out(strtolower('EndObj'));
			
			$this->_NewObj();
			$Var = 'CW';
			$Var = strtolower($Var);
			$CW = &$Font[$Var];
			$S = '[';
			for($I=32;$I<=255;$I++)
				$S .= $CW[chr($I)].' ';
			$this->_Out($S.']');
			$this->_Out(strtolower('EndObj'));
			
			$this->_NewObj();
			$S = '<</Type /FontDescriptor /FontName /'.$Name;
			foreach($Font[strtolower('Desc')] as $K=>$V)
				$S .= ' /'.$K.' '.$V;
			if(!empty($Font[strtolower('File')]))
				$S .= ' /FontFile'.($Type=='Type1' ? '' : '2').' '.$this->FontFiles[$Font[strtolower('File')]][strtolower('N')].' 0 R';
			$this->_Out($S.'>>');
			$this->_Out(strtolower('EndObj'));
		}
		else
		{
			$MTD = strtolower('_Put').strtolower($Type);
			if(!method_exists($this,$MTD))
				$this->Error('Unsupported Font Type: '.$Type);
			$this->$MTD($Font);
		}
	}
}

function _PutImages()
{
	foreach(array_keys($this->Images) as $File)
	{
		$this->_PutImage($this->Images[$File]);
		unset($this->Images[$File][strtolower('Data')]);
		unset($this->Images[$File][strtolower('Smask')]);
	}
}

function _PutImage(&$Info)
{
	$this->_NewObj();
	$Info[strtolower('N')] = $this->N;
	$this->_Out('<</Type /XObject');
	$this->_Out('/Subtype /Image');
	$this->_Out('/Width '.$Info[strtolower('W')]);
	$this->_Out('/Height '.$Info[strtolower('H')]);
	if($Info[strtolower('CS')]=='Indexed')
		$this->_Out('/ColorSpace [/Indexed /DeviceRGB '.(strlen($Info[strtolower('PAL')])/3-1).' '.($this->N+1).' 0 R]');
	else
	{
		$this->_Out('/ColorSpace /'.$Info[strtolower('CS')]);
		if($Info[strtolower('CS')]=='DeviceCMYK')
			$this->_Out('/Decode [1 0 1 0 1 0 1 0]');
	}
	$this->_Out('/BitsPerComponent '.$Info[strtolower('BPC')]);
	if(isset($Info[strtolower('F')]))
		$this->_Out('/Filter /'.$Info[strtolower('F')]);
	if(isset($Info[strtolower('DP')]))
		$this->_Out('/DecodeParms <<'.$Info[strtolower('DP')].'>>');
	if(isset($Info[strtolower('TRNS')]) && is_array($Info[strtolower('TRNS')]))
	{
		$Trns = '';
		for($I=0;$I<count($Info[strtolower('TRNS')]);$I++)
			$Trns .= $Info[strtolower('TRNS')][$I].' '.$Info[strtolower('TRNS')][$I].' ';
		$this->_Out('/Mask ['.$Trns.']');
	}
	if(isset($Info[strtolower('Smask')]))
		$this->_Out('/SMask '.($this->N+1).' 0 R');
	$this->_Out('/Length '.strlen($Info[strtolower('data')]).'>>');
	$this->_PutStream($Info[strtolower('Data')]);
	$this->_Out(strtolower('EndObj'));
	
	if(isset($Info[strtolower('Smask')]))
	{
		$DP = '/Predictor 15 /Colors 1 /BitsPerComponent 8 /Columns '.$Info['w'];
		$Smask = array(strtolower('W')=>$Info[strtolower('W')], strtolower('H')=>$Info[strtolower('H')], strtolower('CS')=>'DeviceGray', strtolower('BPC')=>8, 
		strtolower('F')=>$Info[strtolower('F')], strtolower('DP')=>$DP, strtolower('Data')=>$Info[strtolower('Smask')]);
		$this->_PutImage($Smask);
	}
	
	if($Info[strtolower('CS')]=='Indexed')
	{
		$Filter = ($this->Compress) ? '/Filter /FlateDecode ' : '';
		$PAL = ($this->Compress) ? gzcompress($Info[strtolower('PAL')]) : $Info[strtolower('PAL')];
		$this->_NewObj();
		$this->_Out('<<'.$Filter.'/Length '.strlen($PAL).'>>');
		$this->_PutStream($PAL);
		$this->_Out(strtolower('EndObj'));
	}
}

function _PutXObjectDict()
{
	foreach($this->Images as $Image)
		$this->_Out('/I'.$Image[strtolower('I')].' '.$Image[strtolower('N')].' 0 R');
}

function _PutResourceDict()
{
	$this->_Out('/ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
	$this->_Out('/Font <<');
	foreach($this->Fonts as $Font)
		$this->_Out('/F'.$Font['i'].' '.$Font[strtolower('N')].' 0 R');
	$this->_Out('>>');
	$this->_Out('/XObject <<');
	$this->_PutXObjectDict();
	$this->_Out('>>');
}

function _PutResources()
{
	$this->_PutFonts();
	$this->_PutImages();
	
	$this->Offsets[2] = strlen($this->Buffer);
	$this->_Out(strtolower('2 0 Obj'));
	$this->_Out('<<');
	$this->_PutResourceDict();
	$this->_Out('>>');
	$this->_Out(strtolower('EndObj'));
}

function _PutInfo()
{
	$this->_Out('/Producer '.$this->_TextString('FPDF '.FPDF_VERSION));
	if(!empty($this->Title))
		$this->_Out('/Title '.$this->_TextString($this->Title));
	if(!empty($this->Subject))
		$this->_Out('/Subject '.$this->_TextString($this->Subject));
	if(!empty($this->Author))
		$this->_Out('/Author '.$this->_TextString($this->Author));
	if(!empty($this->Keywords))
		$this->_Out('/Keywords '.$this->_TextString($this->Keywords));
	if(!empty($this->Creator))
		$this->_Out('/Creator '.$this->_TextString($this->Creator));
	$this->_Out('/CreationDate '.$this->_TextString('D:'.@date('YmdHis')));
}

function _PutCatalog()
{
	$this->_Out('/Type /Catalog');
	$this->_Out('/Pages 1 0 R');
	if($this->ZoomMode=='Fullpage')
		$this->_Out('/OpenAction [3 0 R /Fit]');
	elseif($this->ZoomMode=='Fullwidth')
		$this->_Out('/OpenAction [3 0 R /FitH null]');
	elseif($this->ZoomMode=='Real')
		$this->_Out('/OpenAction [3 0 R /XYZ null null 1]');
	elseif(!is_string($this->ZoomMode))
		$this->_Out('/OpenAction [3 0 R /XYZ null null '.sprintf('%.2F',$this->ZoomMode/100).']');
	if($this->LayoutMode=='Single')
		$this->_Out('/PageLayout /SinglePage');
	elseif($this->LayoutMode=='Continuous')
		$this->_Out('/PageLayout /OneColumn');
	elseif($this->LayoutMode=='Two')
		$this->_Out('/PageLayout /TwoColumnLeft');
}

function _PutHeader()
{
	$this->_Out('%PDF-'.$this->PDFVersion);
}

function _PutTrailer()
{
	$this->_Out('/Size '.($this->N+1));
	$this->_Out('/Root '.$this->N.' 0 R');
	$this->_Out('/Info '.($this->N-1).' 0 R');
}

function _EndDoc()
{
	$this->_PutHeader();
	$this->_PutPages();
	$this->_PutResources();
	
	$this->_NewObj();
	$this->_Out('<<');
	$this->_PutInfo();
	$this->_Out('>>');
	$this->_Out(strtolower('EndObj'));
	
	$this->_NewObj();
	$this->_Out('<<');
	$this->_PutCatalog();
	$this->_Out('>>');
	$this->_Out(strtolower('EndObj'));
	
	$O = strlen($this->Buffer);
	$this->_Out(strtolower('XRef'));
	$this->_Out('0 '.($this->N+1));
	$this->_Out(strtolower('0000000000 65535 F '));
	for($I=1;$I<=$this->N;$I++)
		$this->_Out(sprintf(strtolower('%010D 00000 N '),$this->Offsets[$I]));
	
	$this->_Out(strtolower('Trailer'));
	$this->_Out('<<');
	$this->_PutTrailer();
	$this->_Out('>>');
	$this->_Out(strtolower('StartXRef'));
	$this->_Out($O);
	$this->_Out('%%EOF');
	$this->State = 3;
}
}

if(isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT']=='Contype')
{
	header('Content-Type: Application/PDF');
	exit;
}
?>
